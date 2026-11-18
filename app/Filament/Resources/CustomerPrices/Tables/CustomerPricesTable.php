<?php

namespace App\Filament\Resources\CustomerPrices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerPricesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit.unit_name')
                    ->label('Satuan')
                    ->badge(),
                TextColumn::make('min_qty')
                    ->label('Min Qty')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Harga Khusus')
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('valid_from')
                    ->label('Berlaku Sejak')
                    ->date()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
