<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\StockReservation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public function __construct(protected StockService $stockService) {}

    /**
     * Tahan (hold) stok saat pembeli melakukan checkout.
     * Menggunakan baris kunci lockForUpdate() untuk mencegah race condition.
     *
     * @param  array  $items  Array of ['product_id' => int, 'qty_base' => int]
     * @return Collection<int, StockReservation>
     *
     * @throws InsufficientStockException
     */
    public function reserve(int $orderId, int $warehouseId, array $items, CarbonInterface $expiresAt): Collection
    {
        return DB::transaction(function () use ($orderId, $warehouseId, $items, $expiresAt) {
            $createdReservations = new Collection;

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $qtyBase = (int) $item['qty_base'];

                if ($qtyBase <= 0) {
                    continue;
                }

                $this->stockService->ensureLevel($productId, $warehouseId);

                // 1. Kunci baris stok level
                $level = DB::table('stock_levels')
                    ->where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();

                $available = $level ? ((int) $level->on_hand - (int) $level->reserved) : 0;

                if (! $level || $available < $qtyBase) {
                    throw new InsufficientStockException(
                        $productId,
                        $qtyBase,
                        $available,
                        "Stok produk ID {$productId} tidak mencukupi untuk direservasi (tersedia: {$available}, diminta: {$qtyBase})."
                    );
                }

                // 2. Naikkan reserved
                DB::table('stock_levels')
                    ->where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->increment('reserved', $qtyBase);

                // 3. Buat entri reservasi aktif
                $reservation = StockReservation::create([
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'qty_base' => $qtyBase,
                    'status' => ReservationStatus::ACTIVE,
                    'expires_at' => $expiresAt,
                ]);

                $createdReservations->push($reservation);
            }

            return $createdReservations;
        });
    }

    /**
     * Lepas reservasi (misal order dibatalkan oleh pembeli atau ditolak oleh admin).
     *
     * @param  ReservationStatus  $targetStatus  RELEASED atau REJECTED
     */
    public function release(int $orderId, ReservationStatus $targetStatus = ReservationStatus::RELEASED): void
    {
        DB::transaction(function () use ($orderId, $targetStatus) {
            $reservations = StockReservation::where('order_id', $orderId)
                ->where('status', ReservationStatus::ACTIVE)
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                DB::table('stock_levels')
                    ->where('product_id', $reservation->product_id)
                    ->where('warehouse_id', $reservation->warehouse_id)
                    ->decrement('reserved', $reservation->qty_base);

                $reservation->update(['status' => $targetStatus]);
            }
        });
    }

    /**
     * Finalisasi reservasi menjadi barang keluar saat pembayaran lunas.
     * Mengubah status reservasi menjadi CONSUMED dan menurunkan on_hand & reserved
     * secara atomik dalam satu update statement via StockService::fulfillFifoOutbound(..., fromReservation: true).
     *
     * @return array Hasil alokasi FIFO dan COGS
     */
    public function consume(Order $order, ?int $userId = null): array
    {
        return DB::transaction(function () use ($order, $userId) {
            $reservations = StockReservation::where('order_id', $order->id)
                ->where('status', ReservationStatus::ACTIVE)
                ->lockForUpdate()
                ->get();

            $fifoItems = [];
            foreach ($order->items as $orderItem) {
                $fifoItems[] = [
                    'product_id' => $orderItem->product_id,
                    'qty_base' => $orderItem->qty_base,
                    'order_item_id' => $orderItem->id,
                ];
            }

            // 1. Jalankan pemotongan fisik FIFO melalui StockService (on_hand & reserved turun bersamaan)
            $fulfillment = $this->stockService->fulfillFifoOutbound(
                warehouseId: $order->warehouse_id,
                items: $fifoItems,
                refType: 'order',
                refId: $order->id,
                userId: $userId,
                fromReservation: true
            );

            // 2. Tandai reservasi menjadi CONSUMED
            foreach ($reservations as $reservation) {
                $reservation->update(['status' => ReservationStatus::CONSUMED]);
            }

            // 3. Simpan relasi batch dan update COGS di item order
            foreach ($order->items as $orderItem) {
                $itemAlloc = $fulfillment['allocations'][$orderItem->product_id] ?? null;
                if ($itemAlloc) {
                    $orderItem->update(['cogs_total' => $itemAlloc['cogs']]);
                    foreach ($itemAlloc['batches'] as $batchAlloc) {
                        $orderItem->batchAllocations()->create([
                            'stock_batch_id' => $batchAlloc['stock_batch_id'],
                            'qty_base' => $batchAlloc['qty_base'],
                            'unit_cost' => $batchAlloc['unit_cost'],
                        ]);
                    }
                }
            }

            // 4. Update order HPP & Status
            $order->update([
                'cogs_total' => $fulfillment['total_cogs'],
                'status' => OrderStatus::PAID,
                'paid_at' => now(),
            ]);

            return $fulfillment;
        });
    }

    /**
     * Scheduled job: Melepas seluruh reservasi yang telah melewati batas expires_at.
     *
     * @return int Jumlah reservasi yang di-expire
     */
    public function expireOverdue(): int
    {
        return DB::transaction(function () {
            $overdueReservations = StockReservation::where('status', ReservationStatus::ACTIVE)
                ->where('expires_at', '<', now())
                ->lockForUpdate()
                ->get();

            if ($overdueReservations->isEmpty()) {
                return 0;
            }

            $affectedOrderIds = [];

            foreach ($overdueReservations as $reservation) {
                DB::table('stock_levels')
                    ->where('product_id', $reservation->product_id)
                    ->where('warehouse_id', $reservation->warehouse_id)
                    ->decrement('reserved', $reservation->qty_base);

                $reservation->update(['status' => ReservationStatus::EXPIRED]);
                $affectedOrderIds[$reservation->order_id] = true;
            }

            // Update status orders menjadi EXPIRED jika masih menunggu pembayaran / ongkir
            foreach (array_keys($affectedOrderIds) as $orderId) {
                Order::where('id', $orderId)
                    ->whereIn('status', [OrderStatus::MENUNGGU_ONGKIR, OrderStatus::PENDING_PAYMENT])
                    ->update(['status' => OrderStatus::EXPIRED]);
            }

            return $overdueReservations->count();
        });
    }
}
