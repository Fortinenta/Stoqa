<?php

namespace App\Filament\Resources\StockMovements\Tables;

use App\Enums\StockMovementType;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('Gudang')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (StockMovementType $state): string => match ($state) {
                        StockMovementType::IN => 'success',
                        StockMovementType::OUT => 'danger',
                        StockMovementType::ADJUST => 'warning',
                    }),
                TextColumn::make('qty_base')
                    ->label('Jumlah Mutasi')
                    ->formatStateUsing(fn ($state) => ($state > 0 ? "+{$state}" : "{$state}"))
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('ref_info')
                    ->label('Referensi')
                    ->state(fn ($record) => $record->ref_type ? "{$record->ref_type} #{$record->ref_id}" : '-')
                    ->badge(),
                TextColumn::make('creator.name')
                    ->label('Dicatat Oleh')
                    ->placeholder('Sistem'),
                TextColumn::make('note')
                    ->label('Keterangan')
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        StockMovementType::IN->value => 'Barang Masuk (IN)',
                        StockMovementType::OUT->value => 'Barang Keluar (OUT)',
                        StockMovementType::ADJUST->value => 'Penyesuaian (ADJUST)',
                    ]),
            ]);
    }
}
