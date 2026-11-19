<?php

namespace App\Filament\Resources\InboundOrders\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InboundOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('supplier.name')
                    ->searchable(),
                TextColumn::make('warehouse.name')
                    ->searchable(),
                TextColumn::make('received_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('shipping_cost')
                    ->money()
                    ->sortable(),
                TextColumn::make('other_cost')
                    ->money()
                    ->sortable(),
                TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // Data transaksi penerimaan barang tidak boleh diedit atau dihapus sembarangan
            ])
            ->toolbarActions([
                // Tidak ada bulk delete untuk transaksi stok
            ]);
    }
}
