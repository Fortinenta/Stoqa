<?php

namespace App\Filament\Resources\CustomerPrices\Schemas;

use App\Models\ProductUnit;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerPriceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Pelanggan')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('unit_id', null))
                    ->required(),
                Select::make('unit_id')
                    ->label('Satuan')
                    ->options(function (callable $get) {
                        $productId = $get('product_id');
                        if (! $productId) {
                            return [];
                        }

                        return ProductUnit::where('product_id', $productId)->pluck('unit_name', 'id');
                    })
                    ->required(),
                TextInput::make('min_qty')
                    ->label('Jumlah Minimum')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required(),
                TextInput::make('price')
                    ->label('Harga Khusus')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                DatePicker::make('valid_from')
                    ->label('Berlaku Mulai')
                    ->default(now()),
            ]);
    }
}
