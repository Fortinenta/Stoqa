<?php

namespace App\Filament\Resources\StockMovements;

use App\Filament\Resources\StockMovements\Pages\ListStockMovements;
use App\Filament\Resources\StockMovements\Tables\StockMovementsTable;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationLabel = 'Buku Mutasi Stok (Ledger)';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

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
