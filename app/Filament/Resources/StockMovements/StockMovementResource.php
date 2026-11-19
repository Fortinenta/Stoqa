<?php

namespace App\Filament\Resources\StockMovements;

use App\Filament\Resources\StockMovements\Pages\ListStockMovements;
use App\Filament\Resources\StockMovements\Tables\StockMovementsTable;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationLabel = 'Jejak Stok';

    protected static ?string $modelLabel = 'Mutasi Stok';

    protected static ?string $pluralModelLabel = 'Jejak Stok';

    protected static string|UnitEnum|null $navigationGroup = 'Gudang';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'phosphor-clock-counter-clockwise-duotone';

    protected static string|BackedEnum|null $activeNavigationIcon = 'phosphor-clock-counter-clockwise-fill';

    public static function table(Table $table): Table
    {
        return StockMovementsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false; // Append-only
    }

    public static function canEdit(Model $record): bool
    {
        return false; // Append-only
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Append-only
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockMovements::route('/'),
        ];
    }
}
