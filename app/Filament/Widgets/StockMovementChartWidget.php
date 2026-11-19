<?php

namespace App\Filament\Widgets;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Filament\Widgets\ChartWidget;

class StockMovementChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = 'Arus Keluar Masuk Fisik Gudang (7 Hari)';

    protected ?string $description = 'Volume kuantitas barang fisik masuk (Inbound) vs keluar (Penjualan/Opname)';

    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());

        $labels = [];
        $inboundData = [];
        $outboundData = [];

        foreach ($days as $day) {
            $labels[] = $day->format('d M');
            $nextDay = (clone $day)->endOfDay();

            $inbound = (int) (StockMovement::where('type', StockMovementType::IN)
                ->whereBetween('created_at', [$day, $nextDay])
                ->sum('qty_base') ?? 0);

            $outbound = abs((int) (StockMovement::where('type', StockMovementType::OUT)
                ->whereBetween('created_at', [$day, $nextDay])
                ->sum('qty_base') ?? 0));

            $inboundData[] = $inbound;
            $outboundData[] = $outbound;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Barang Masuk (Inbound)',
                    'data' => $inboundData,
                    'backgroundColor' => '#34A095',
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Barang Keluar (Outbound)',
                    'data' => $outboundData,
                    'backgroundColor' => '#FB7185',
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
