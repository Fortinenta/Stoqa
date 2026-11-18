<?php

namespace App\Filament\Resources\InboundOrders;

use App\Filament\Resources\InboundOrders\Pages\CreateInboundOrder;
use App\Filament\Resources\InboundOrders\Pages\EditInboundOrder;
use App\Filament\Resources\InboundOrders\Pages\ListInboundOrders;
use App\Filament\Resources\InboundOrders\Schemas\InboundOrderForm;
use App\Filament\Resources\InboundOrders\Tables\InboundOrdersTable;
use App\Models\InboundOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InboundOrderResource extends Resource
{
    protected static ?string $model = InboundOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return InboundOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InboundOrdersTable::configure($table);
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
            'edit' => EditInboundOrder::route('/{record}/edit'),
        ];
    }
}
