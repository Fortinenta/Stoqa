<?php

namespace App\Filament\Resources\CustomerPrices;

use App\Filament\Resources\CustomerPrices\Pages\CreateCustomerPrice;
use App\Filament\Resources\CustomerPrices\Pages\EditCustomerPrice;
use App\Filament\Resources\CustomerPrices\Pages\ListCustomerPrices;
use App\Filament\Resources\CustomerPrices\Schemas\CustomerPriceForm;
use App\Filament\Resources\CustomerPrices\Tables\CustomerPricesTable;
use App\Models\CustomerPrice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CustomerPriceResource extends Resource
{
    protected static ?string $model = CustomerPrice::class;

    protected static ?string $navigationLabel = 'Harga Langganan';

    protected static ?string $modelLabel = 'Harga Langganan';

    protected static ?string $pluralModelLabel = 'Harga Langganan';

    protected static string|UnitEnum|null $navigationGroup = 'Etalase';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'phosphor-seal-percent-duotone';

    protected static string|BackedEnum|null $activeNavigationIcon = 'phosphor-seal-percent-fill';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return CustomerPriceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerPricesTable::configure($table);
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
            'index' => ListCustomerPrices::route('/'),
            'create' => CreateCustomerPrice::route('/create'),
            'edit' => EditCustomerPrice::route('/{record}/edit'),
        ];
    }
}
