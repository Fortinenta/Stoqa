<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\ProductUnit;
use App\Services\StockService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $itemsData = $data['items'] ?? [];
            unset($data['items']);

            $subtotal = 0.0;
            $preparedItems = [];

            foreach ($itemsData as $item) {
                $qty = (int) $item['qty'];
                $unitPrice = (float) $item['unit_price'];
                $lineTotal = $qty * $unitPrice;
                $subtotal += $lineTotal;

                $unit = ProductUnit::find($item['unit_id']);
                $conversion = $unit ? $unit->conversion_to_base : 1;
                $qtyBase = $qty * $conversion;

                $preparedItems[] = [
                    'product_id' => (int) $item['product_id'],
                    'unit_id' => (int) $item['unit_id'],
                    'qty' => $qty,
                    'qty_base' => $qtyBase,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $shipping = (float) ($data['shipping_cost'] ?? 0);
            $other = (float) ($data['other_cost'] ?? 0);
            $total = $subtotal + $shipping + $other;

            $data['status'] = OrderStatus::PAID;
            $data['subtotal'] = $subtotal;
            $data['total'] = $total;
            $data['paid_at'] = now();
            $data['invoiced_at'] = now();

            $order = Order::create($data);

            // Generate nomor invoice
            $invoiceNo = 'INV/'.now()->format('Y/m').'/'.str_pad($order->id, 5, '0', STR_PAD_LEFT);
            $order->update(['invoice_no' => $invoiceNo]);

            // Buat order items
            $createdOrderItems = [];
            $fifoItems = [];
            foreach ($preparedItems as $prep) {
                $orderItem = $order->items()->create([
                    'product_id' => $prep['product_id'],
                    'unit_id' => $prep['unit_id'],
                    'qty' => $prep['qty'],
                    'qty_base' => $prep['qty_base'],
                    'unit_price' => $prep['unit_price'],
                    'line_total' => $prep['line_total'],
                    'price_source' => 'manual',
                ]);
                $createdOrderItems[$prep['product_id']] = $orderItem;
                $fifoItems[] = [
                    'product_id' => $prep['product_id'],
                    'qty_base' => $prep['qty_base'],
                    'order_item_id' => $orderItem->id,
                ];
            }

            // Jalankan pemotongan FIFO lewat StockService
            $stockService = app(StockService::class);
            $fulfillment = $stockService->fulfillFifoOutbound(
                warehouseId: $order->warehouse_id,
                items: $fifoItems,
                refType: 'order',
                refId: $order->id,
                userId: auth()->id()
            );

            // Simpan rincian alokasi batch dan HPP
            foreach ($createdOrderItems as $prodId => $orderItem) {
                $alloc = $fulfillment['allocations'][$prodId] ?? null;
                if ($alloc) {
                    $orderItem->update(['cogs_total' => $alloc['cogs']]);
                    foreach ($alloc['batches'] as $batchAlloc) {
                        $orderItem->batchAllocations()->create([
                            'stock_batch_id' => $batchAlloc['stock_batch_id'],
                            'qty_base' => $batchAlloc['qty_base'],
                            'unit_cost' => $batchAlloc['unit_cost'],
                        ]);
                    }
                }
            }

            $order->update(['cogs_total' => $fulfillment['total_cogs']]);

            return $order;
        });
    }
}
