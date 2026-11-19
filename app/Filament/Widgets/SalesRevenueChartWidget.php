<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class SalesRevenueChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = 'Tren Omzet & HPP Penjualan (7 Hari)';

    protected ?string $description = 'Komparasi nilai penjualan harian terhadap modal FIFO barang keluar';

    protected ?string $maxHeight = '280px';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());

        $labels = [];
        $revenueData = [];
        $cogsData = [];
        $profitData = [];

        foreach ($days as $day) {
            $dateString = $day->format('d M');
            $labels[] = $dateString;

            $nextDay = (clone $day)->endOfDay();

            $orders = Order::whereIn('status', [OrderStatus::PAID, OrderStatus::COMPLETED, OrderStatus::SHIPPED])
                ->where(function ($query) use ($day, $nextDay) {
                    $query->whereBetween('paid_at', [$day, $nextDay])
                        ->orWhere(function ($q) use ($day, $nextDay) {
                            $q->whereNull('paid_at')->whereBetween('created_at', [$day, $nextDay]);
                        });
                })
                ->get();

            $rev = (float) $orders->sum('subtotal');
            $cogs = (float) $orders->sum('cogs_total');
            $profit = max(0, $rev - $cogs);

            $revenueData[] = round($rev);
            $cogsData[] = round($cogs);
            $profitData[] = round($profit);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Omzet Penjualan (Rp)',
                    'data' => $revenueData,
                    'borderColor' => '#278177',
                    'backgroundColor' => 'rgba(39, 129, 119, 0.15)',
                    'fill' => 'start',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'HPP Modal FIFO (Rp)',
                    'data' => $cogsData,
                    'borderColor' => '#F4B860',
                    'backgroundColor' => 'rgba(244, 184, 96, 0.15)',
                    'fill' => 'start',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Laba Kotor (Rp)',
                    'data' => $profitData,
                    'borderColor' => '#10B981',
                    'backgroundColor' => '#10B981',
                    'fill' => false,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
