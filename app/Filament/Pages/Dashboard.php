<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Ringkasan';

    protected static ?string $title = 'Ringkasan Stoqa';

    protected static ?int $navigationSort = -2;

    protected static string|BackedEnum|null $navigationIcon = 'phosphor-chart-donut-duotone';

    protected static string|BackedEnum|null $activeNavigationIcon = 'phosphor-chart-donut-fill';
}
