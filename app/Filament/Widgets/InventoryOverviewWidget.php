<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockBatch;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1. Total Nilai Persediaan
        $inventoryValue = StockBatch::where('qty_remaining', '>', 0)
            ->selectRaw('SUM(qty_remaining * unit_cost_landed) as total_value')
            ->value('total_value') ?? 0;

        // 2. Laba Kotor Bulan Ini
        $startOfMonth = now()->startOfMonth();
        $monthlyOrders = Order::whereIn('status', [OrderStatus::PAID, OrderStatus::COMPLETED])
            ->where('paid_at', '>=', $startOfMonth)
            ->whereNotNull('cogs_total')
            ->get();

        $monthlyGrossProfit = $monthlyOrders->sum(function ($order) {
            return (float) $order->subtotal - (float) $order->cogs_total;
        });

        // 3. Order Menunggu Ongkir (Batas 3 Jam)
        $waitingShippingCount = Order::where('status', OrderStatus::MENUNGGU_ONGKIR)->count();

        // 4. Produk Menipis / Habis
        $lowStockCount = 0;
        $products = Product::where('is_active', true)->with('stockLevels')->get();
        foreach ($products as $p) {
            $available = max(0, $p->stockLevels->sum('on_hand') - $p->stockLevels->sum('reserved'));
            if ($available <= $p->min_stock) {
                $lowStockCount++;
            }
        }

        return [
            Stat::make('Nilai Persediaan Gudang', 'Rp '.number_format($inventoryValue, 0, ',', '.'))
                ->description('Total modal stok fisik tersisa')
                ->icon('heroicon-o-cube')
                ->color('primary'),

            Stat::make('Laba Kotor Bulan Ini', 'Rp '.number_format($monthlyGrossProfit, 0, ',', '.'))
                ->description('Total penjualan - HPP FIFO')
                ->icon('heroicon-o-chart-bar')
                ->color('success'),

            Stat::make('Menunggu Ongkir (3 Jam)', $waitingShippingCount)
                ->description('Segera proses agar tidak kedaluwarsa')
                ->icon('heroicon-o-clock')
                ->color($waitingShippingCount > 0 ? 'warning' : 'gray'),

            Stat::make('Peringatan Stok Menipis', $lowStockCount.' Produk')
                ->description('Tersedia ≤ Batas minimum')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
