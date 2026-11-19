<?php

namespace App\Filament\Resources\InboundOrders;

use App\Filament\Resources\InboundOrders\Pages\CreateInboundOrder;
use App\Filament\Resources\InboundOrders\Pages\ListInboundOrders;
use App\Filament\Resources\InboundOrders\Schemas\InboundOrderForm;
use App\Filament\Resources\InboundOrders\Tables\InboundOrdersTable;
use App\Models\InboundOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class InboundOrderResource extends Resource
{
    protected static ?string $model = InboundOrder::class;

    protected static ?string $navigationLabel = 'Penerimaan Barang';

    protected static ?string $modelLabel = 'Penerimaan';

    protected static ?string $pluralModelLabel = 'Penerimaan Barang';

    protected static string|UnitEnum|null $navigationGroup = 'Gudang';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'phosphor-package-duotone';

    protected static string|BackedEnum|null $activeNavigationIcon = 'phosphor-package-fill';

    public static function form(Schema $schema): Schema
    {
        return InboundOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InboundOrdersTable::configure($table);
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
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
            'index' => ListInboundOrders::route('/'),
            'create' => CreateInboundOrder::route('/create'),
        ];
    }
}
