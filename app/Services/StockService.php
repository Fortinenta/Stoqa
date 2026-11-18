<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\InconsistentStockException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidAdjustmentException;
use App\Models\InboundOrder;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Pastikan baris stock_levels untuk kombinasi product & warehouse telah ada.
     */
    public function ensureLevel(int $productId, int $warehouseId): void
    {
        DB::table('stock_levels')->insertOrIgnore([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'on_hand' => 0,
            'reserved' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Cek stok tersedia (on_hand - reserved).
     */
    public function available(int $productId, int $warehouseId): int
    {
        $this->ensureLevel($productId, $warehouseId);

        $level = DB::table('stock_levels')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (! $level) {
            return 0;
        }

        return max(0, (int) $level->on_hand - (int) $level->reserved);
    }

    /**
     * Mencatat barang masuk: alokasi biaya proporsional dengan bc*, landed cost,
     * batch baru, on_hand naik, movement IN.
     * Item terakhir menerima sisa alokasi agar tidak ada galat pembulatan.
     *
     * @param  array  $data  [warehouse_id, received_date, shipping_cost, other_cost, note, items: [...], supplier_id]
     */
    public function receive(array $data, ?int $userId = null): InboundOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $order = InboundOrder::create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'received_date' => $data['received_date'] ?? now()->toDateString(),
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'other_cost' => $data['other_cost'] ?? 0,
                'note' => $data['note'] ?? null,
                'created_by' => $userId,
            ]);

            $extra = bcadd((string) $order->shipping_cost, (string) $order->other_cost, 2);
            $total = array_reduce($data['items'], function ($c, $i) {
                return bcadd($c, bcmul((string) $i['qty_base'], (string) $i['unit_price'], 2), 2);
            }, '0.00');

            $allocated = '0.00';
            $lastKey = array_key_last($data['items']);

            foreach ($data['items'] as $key => $row) {
                $line = bcmul((string) $row['qty_base'], (string) $row['unit_price'], 2);
                $share = ($key === $lastKey || bccomp($total, '0', 2) === 0)
                    ? bcsub($extra, $allocated, 2)
                    : bcdiv(bcmul($extra, $line, 4), $total, 2);

                $allocated = bcadd($allocated, $share, 2);
                $landed = bcdiv(bcadd($line, $share, 2), (string) $row['qty_base'], 4);

                $item = $order->items()->create([
                    'product_id' => $row['product_id'],
                    'qty_base' => $row['qty_base'],
                    'unit_price' => $row['unit_price'],
                    'allocated_cost' => $share,
                ]);

                StockBatch::create([
                    'product_id' => $row['product_id'],
                    'warehouse_id' => $order->warehouse_id,
                    'inbound_item_id' => $item->id,
                    'qty_initial' => $row['qty_base'],
                    'qty_remaining' => $row['qty_base'],
                    'unit_cost_landed' => $landed,
                    'received_at' => now(),
                ]);

                $this->ensureLevel($row['product_id'], $order->warehouse_id);
                DB::table('stock_levels')
                    ->where('product_id', $row['product_id'])
                    ->where('warehouse_id', $order->warehouse_id)
                    ->increment('on_hand', $row['qty_base']);

                StockMovement::create([
                    'product_id' => $row['product_id'],
                    'warehouse_id' => $order->warehouse_id,
                    'type' => StockMovementType::IN,
                    'qty_base' => $row['qty_base'],
                    'ref_type' => 'inbound_order',
                    'ref_id' => $order->id,
                    'note' => 'Penerimaan barang masuk #'.$order->id,
                    'created_by' => $userId,
                    'created_at' => now(),
                ]);
            }

            return $order;
        });
    }

    /**
     * Wrapper receiveInbound untuk kompatibilitas Filament Form.
     */
    public function receiveInbound(array $inboundData, array $items, ?int $userId = null): InboundOrder
    {
        $inboundData['items'] = $items;

        return $this->receive($inboundData, $userId);
    }

    /**
     * Barang keluar dengan FIFO:
     * 1. Kunci stock_levels dengan lockForUpdate().
     * 2. Periksa kecukupan stok.
     * 3. Ambil batch tertua dengan lockForUpdate().
     * 4. Potong dari batch, hitung akumulasi HPP.
     * 5. Turunkan on_hand (dan reserved bila fromReservation) secara atomik.
     * 6. Catat movement OUT bernilai negatif.
     *
     * @return array ['cogs' => string, 'batches' => array]
     *
     * @throws InsufficientStockException
     * @throws InconsistentStockException
     */
    public function issue(
        int $productId,
        int $warehouseId,
        int $qtyBase,
        bool $fromReservation = false,
        ?string $refType = 'order',
        ?int $refId = null,
        ?int $userId = null
    ): array {
        return DB::transaction(function () use ($productId, $warehouseId, $qtyBase, $fromReservation, $refType, $refId, $userId) {
            $this->ensureLevel($productId, $warehouseId);

            $level = DB::table('stock_levels')
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            $enough = $fromReservation
                ? (int) $level->on_hand >= $qtyBase
                : ((int) $level->on_hand - (int) $level->reserved) >= $qtyBase;

            if (! $enough) {
                $avail = $fromReservation ? (int) $level->on_hand : ((int) $level->on_hand - (int) $level->reserved);
                throw new InsufficientStockException($productId, $qtyBase, $avail);
            }

            $batches = StockBatch::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->where('qty_remaining', '>', 0)
                ->orderBy('received_at', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $remaining = $qtyBase;
            $cogs = '0.0000';
            $used = [];

            foreach ($batches as $batch) {
                if ($remaining === 0) {
                    break;
                }
                $take = min($remaining, $batch->qty_remaining);
                $batch->decrement('qty_remaining', $take);

                $cogs = bcadd($cogs, bcmul((string) $take, (string) $batch->unit_cost_landed, 4), 4);
                $used[] = [
                    'stock_batch_id' => $batch->id,
                    'qty_base' => $take,
                    'unit_cost' => $batch->unit_cost_landed,
                ];
                $remaining -= $take;
            }

            if ($remaining > 0) {
                throw new InconsistentStockException($productId);
            }

            DB::table('stock_levels')
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->update($fromReservation
                    ? ['on_hand' => DB::raw("on_hand - $qtyBase"), 'reserved' => DB::raw("reserved - $qtyBase")]
                    : ['on_hand' => DB::raw("on_hand - $qtyBase")]);

            StockMovement::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => StockMovementType::OUT,
                'qty_base' => -$qtyBase,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'note' => "Pengeluaran stok {$refType} #".($refId ?? '-'),
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            return [
                'cogs' => $cogs,
                'batches' => $used,
            ];
        });
    }

    /**
     * Wrapper fulfillFifoOutbound untuk pemotongan beberapa produk sekaligus dalam order.
     */
    public function fulfillFifoOutbound(
        int $warehouseId,
        array $items,
        string $refType = 'order',
        ?int $refId = null,
        ?int $userId = null,
        bool $fromReservation = false
    ): array {
        return DB::transaction(function () use ($warehouseId, $items, $refType, $refId, $userId, $fromReservation) {
            $totalCogs = '0.0000';
            $allocations = [];

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $qtyBase = (int) $item['qty_base'];
                $orderItemId = $item['order_item_id'] ?? null;

                $result = $this->issue(
                    productId: $productId,
                    warehouseId: $warehouseId,
                    qtyBase: $qtyBase,
                    fromReservation: $fromReservation,
                    refType: $refType,
                    refId: $refId,
                    userId: $userId
                );

                $totalCogs = bcadd($totalCogs, $result['cogs'], 4);

                $allocations[$productId] = [
                    'cogs' => round((float) $result['cogs'], 2),
                    'batches' => array_map(function ($b) use ($orderItemId) {
                        $b['order_item_id'] = $orderItemId;

                        return $b;
                    }, $result['batches']),
                ];
            }

            return [
                'total_cogs' => round((float) $totalCogs, 2),
                'allocations' => $allocations,
            ];
        });
    }

    /**
     * Penyesuaian stok opname fisik.
     *
     * @throws InvalidAdjustmentException
     */
    public function adjust(
        int $productId,
        int $warehouseId,
        int $newQty,
        string $reason,
        ?int $userId = null,
        ?string $unitCost = null,
        ?float $unitCostForNewBatch = null,
    ): int {
        if ($newQty < 0) {
            throw new InvalidAdjustmentException('Stok hasil opname tidak boleh negatif.');
        }
        if (trim($reason) === '') {
            throw new InvalidAdjustmentException('Alasan penyesuaian wajib diisi.');
        }

        return DB::transaction(function () use ($productId, $warehouseId, $newQty, $reason, $userId, $unitCost, $unitCostForNewBatch) {
            $this->ensureLevel($productId, $warehouseId);

            $level = DB::table('stock_levels')
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            $currentOnHand = (int) $level->on_hand;
            $reserved = (int) $level->reserved;

            if ($newQty < $reserved) {
                throw new InvalidAdjustmentException(
                    "Stok opname ({$newQty}) lebih kecil dari stok yang di-hold ({$reserved}). Lepas atau batalkan hold terlebih dahulu."
                );
            }

            $diff = $newQty - $currentOnHand;

            if ($diff === 0) {
                StockMovement::create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'type' => StockMovementType::ADJUST,
                    'qty_base' => 0,
                    'ref_type' => 'opname',
                    'note' => $reason.' (Stok sesuai fisik)',
                    'created_by' => $userId,
                    'created_at' => now(),
                ]);

                return 0;
            }

            DB::table('stock_levels')
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->update(['on_hand' => $newQty, 'updated_at' => now()]);

            if ($diff > 0) {
                // Selisih LEBIH: Buat batch baru hasil opname
                $cost = $unitCost ?? ($unitCostForNewBatch !== null ? (string) $unitCostForNewBatch : null);
                if ($cost === null) {
                    $latestBatch = StockBatch::where('product_id', $productId)
                        ->where('warehouse_id', $warehouseId)
                        ->orderByDesc('received_at')
                        ->orderByDesc('id')
                        ->first();
                    $cost = $latestBatch ? (string) $latestBatch->unit_cost_landed : '0.0000';
                }

                StockBatch::create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'inbound_item_id' => null,
                    'qty_initial' => $diff,
                    'qty_remaining' => $diff,
                    'unit_cost_landed' => $cost,
                    'received_at' => now(),
                ]);
            } else {
                // Selisih KURANG: Potong batch FIFO
                $toDeduct = abs($diff);
                $batches = StockBatch::where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->where('qty_remaining', '>', 0)
                    ->orderBy('received_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $batch) {
                    if ($toDeduct <= 0) {
                        break;
                    }
                    $deduct = min($toDeduct, $batch->qty_remaining);
                    $batch->decrement('qty_remaining', $deduct);
                    $toDeduct -= $deduct;
                }
            }

            StockMovement::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => StockMovementType::ADJUST,
                'qty_base' => $diff,
                'ref_type' => 'opname',
                'note' => $reason,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            return $diff;
        });
    }

    public function adjustStock(int $productId, int $warehouseId, int $actualQtyOnHand, string $reason, ?float $unitCostForNewBatch = null, ?int $userId = null): int
    {
        return $this->adjust(
            productId: $productId,
            warehouseId: $warehouseId,
            newQty: $actualQtyOnHand,
            reason: $reason,
            userId: $userId,
            unitCostForNewBatch: $unitCostForNewBatch
        );
    }
}
