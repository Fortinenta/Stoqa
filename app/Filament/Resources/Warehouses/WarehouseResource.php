<?php

namespace App\Filament\Resources\Warehouses;

use App\Filament\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Resources\Warehouses\Pages\EditWarehouse;
use App\Filament\Resources\Warehouses\Pages\ListWarehouses;
use App\Filament\Resources\Warehouses\Schemas\WarehouseForm;
use App\Filament\Resources\Warehouses\Tables\WarehousesTable;
use App\Models\Warehouse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static ?string $navigationLabel = 'Lokasi Gudang';

    protected static ?string $modelLabel = 'Gudang';

    protected static ?string $pluralModelLabel = 'Lokasi Gudang';

    protected static string|UnitEnum|null $navigationGroup = 'Gudang';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'phosphor-warehouse-duotone';

    protected static string|BackedEnum|null $activeNavigationIcon = 'phosphor-warehouse-fill';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehousesTable::configure($table);
    }

    public static function canDelete(Model $record): bool
    {
        /** @var Warehouse $record */
        return ! $record->is_default && ! $record->stockLevels()->where('on_hand', '>', 0)->exists();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouses::route('/'),
            'create' => CreateWarehouse::route('/create'),
            'edit' => EditWarehouse::route('/{record}/edit'),
        ];
    }
}
