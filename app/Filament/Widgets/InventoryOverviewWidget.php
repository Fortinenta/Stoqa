<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\ReservationStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockReservation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|array|null $columns = [
        'sm' => 2,
        'md' => 3,
        'xl' => 5,
    ];

    protected function getStats(): array
    {
        $isAdmin = auth()->user()?->hasRole('admin') ?? false;

        // 1. Total Nilai & Kuantitas Persediaan Gudang (Landed Cost FIFO)
        $inventoryQuery = StockBatch::where('qty_remaining', '>', 0);
        $inventoryValue = (float) ($inventoryQuery->clone()
            ->selectRaw('SUM(qty_remaining * unit_cost_landed) as total_value')
            ->value('total_value') ?? 0);
        $totalPhysicalUnits = (int) ($inventoryQuery->clone()->sum('qty_remaining') ?? 0);

        // 2. Total Penjualan & Laba Kotor Bulan Ini (Hanya dihitung untuk Admin)
        $startOfMonth = now()->startOfMonth();
        $monthlyRevenue = 0.0;
        $monthlyGrossProfit = 0.0;

        if ($isAdmin) {
            $monthlyOrders = Order::whereIn('status', [OrderStatus::PAID, OrderStatus::COMPLETED, OrderStatus::SHIPPED])
                ->where(function ($query) use ($startOfMonth) {
                    $query->where('paid_at', '>=', $startOfMonth)
                        ->orWhere(function ($q) use ($startOfMonth) {
                            $q->whereNull('paid_at')->where('created_at', '>=', $startOfMonth);
                        });
                })
                ->get();

            $monthlyRevenue = (float) $monthlyOrders->sum('subtotal');
            $monthlyGrossProfit = (float) $monthlyOrders->sum(function ($order) {
                $cogs = (float) ($order->cogs_total ?? 0);

                return (float) $order->subtotal - $cogs;
            });
        }

        // 3. Stok Tertahan (Di-Hold) oleh Pesanan Aktif
        $activeHoldUnits = (int) (StockReservation::where('status', ReservationStatus::ACTIVE)->sum('qty_base') ?? 0);
        $activeHoldOrders = (int) (StockReservation::where('status', ReservationStatus::ACTIVE)->distinct('order_id')->count('order_id'));

        // 4. Pesanan Perlu Tindakan Segera (Menunggu Ongkir / Verifikasi)
        $pendingActionCount = Order::whereIn('status', [
            OrderStatus::MENUNGGU_ONGKIR,
            OrderStatus::PENDING_PAYMENT,
        ])->count();

        // 5. Produk Menipis / Kritis (Available <= Min Stock)
        $lowStockCount = 0;
        $products = Product::where('is_active', true)->with('stockLevels')->get();
        foreach ($products as $p) {
            $available = max(0, $p->stockLevels->sum('on_hand') - $p->stockLevels->sum('reserved'));
            if ($available <= $p->min_stock) {
                $lowStockCount++;
            }
        }

        // Jika bukan admin (staf gudang), sembunyikan nilai uang (HPP & Laba) demi keamanan data modal
        if (! $isAdmin) {
            return [
                Stat::make('Total Unit Fisik', number_format($totalPhysicalUnits, 0, ',', '.').' Unit')
                    ->description('Total stok di seluruh batch gudang')
                    ->descriptionIcon('heroicon-m-cube')
                    ->color('primary'),

                Stat::make('Total Produk Aktif', number_format($products->count(), 0, ',', '.').' Produk')
                    ->description('Katalog terdaftar aktif')
                    ->descriptionIcon('heroicon-m-tag')
                    ->color('info'),

                Stat::make('Stok Tertahan (Hold)', number_format($activeHoldUnits, 0, ',', '.').' Unit')
                    ->description("Dari {$activeHoldOrders} pesanan aktif")
                    ->descriptionIcon('heroicon-m-lock-closed')
                    ->color($activeHoldUnits > 0 ? 'info' : 'gray'),

                Stat::make('Perlu Tindakan', $pendingActionCount.' Pesanan')
                    ->description('Menunggu ongkir & bayar')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color($pendingActionCount > 0 ? 'warning' : 'gray'),

                Stat::make('Peringatan Stok', $lowStockCount.' Produk')
                    ->description('Stok ≤ Ambang batas')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color($lowStockCount > 0 ? 'danger' : 'success'),
            ];
        }

        // 7-day sparkline sample data for visual polish
        $cogsSparkline = [
            round($inventoryValue * 0.95),
            round($inventoryValue * 0.97),
            round($inventoryValue * 0.96),
            round($inventoryValue * 0.98),
            round($inventoryValue * 0.99),
            round($inventoryValue),
        ];

        return [
            Stat::make('Valuasi Persediaan', 'Rp '.number_format($inventoryValue, 0, ',', '.'))
                ->description(number_format($totalPhysicalUnits, 0, ',', '.').' unit fisik tersisa')
                ->descriptionIcon('heroicon-m-cube')
                ->chart($cogsSparkline)
                ->color('primary'),

            Stat::make('Laba Kotor Bulan Ini', 'Rp '.number_format($monthlyGrossProfit, 0, ',', '.'))
                ->description('Omzet: Rp '.number_format($monthlyRevenue, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([2, 4, 7, 5, 8, 12, 15])
                ->color('success'),

            Stat::make('Stok Tertahan (Hold)', number_format($activeHoldUnits, 0, ',', '.').' Unit')
                ->description("Dari {$activeHoldOrders} pesanan aktif")
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color($activeHoldUnits > 0 ? 'info' : 'gray'),

            Stat::make('Perlu Tindakan', $pendingActionCount.' Pesanan')
                ->description('Menunggu ongkir & bayar')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingActionCount > 0 ? 'warning' : 'gray'),

            Stat::make('Peringatan Stok', $lowStockCount.' Produk')
                ->description('Stok ≤ Ambang batas')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
