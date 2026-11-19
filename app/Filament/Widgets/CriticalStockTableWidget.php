<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class CriticalStockTableWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Peringatan Stok Kritis & Perlu Restock';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->where('is_active', true)
                    ->whereHas('stockLevels')
                    ->select('products.*')
                    ->selectRaw('(SELECT COALESCE(SUM(on_hand - reserved), 0) FROM stock_levels WHERE stock_levels.product_id = products.id) as current_available')
                    ->selectRaw('(SELECT COALESCE(SUM(on_hand), 0) FROM stock_levels WHERE stock_levels.product_id = products.id) as total_on_hand')
                    ->selectRaw('(SELECT COALESCE(SUM(reserved), 0) FROM stock_levels WHERE stock_levels.product_id = products.id) as total_reserved')
                    ->whereRaw('(SELECT COALESCE(SUM(on_hand - reserved), 0) FROM stock_levels WHERE stock_levels.product_id = products.id) <= products.min_stock')
                    ->orderByRaw('(SELECT COALESCE(SUM(on_hand - reserved), 0) FROM stock_levels WHERE stock_levels.product_id = products.id) ASC')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Produk')
                    ->description(fn (Product $record): string => 'SKU: '.$record->sku)
                    ->weight(FontWeight::Bold),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('total_on_hand')
                    ->label('Fisik')
                    ->numeric()
                    ->suffix(fn (Product $record): string => ' '.$record->base_unit)
                    ->alignEnd(),

                TextColumn::make('current_available')
                    ->label('Tersedia')
                    ->numeric()
                    ->suffix(fn (Product $record): string => ' '.$record->base_unit)
                    ->badge()
                    ->color(fn ($state): string => $state <= 0 ? 'danger' : 'warning')
                    ->alignEnd(),

                TextColumn::make('min_stock')
                    ->label('Ambang')
                    ->numeric()
                    ->suffix(fn (Product $record): string => ' '.$record->base_unit)
                    ->alignEnd(),
            ])
            ->actions([
                Action::make('preview')
                    ->label('Rincian')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Product $record): string => "Rincian Stok: {$record->name}")
                    ->modalWidth(Width::FourExtraLarge)
                    ->modalContent(fn (Product $record) => view('filament.resources.products.preview-modal', [
                        'product' => $record->loadMissing(['category', 'units', 'prices.unit', 'stockLevels.warehouse', 'stockBatches.warehouse']),
                    ]))
                    ->modalSubmitAction(false)
                    ->extraModalFooterActions([
                        Action::make('editProduct')
                            ->label('Edit Produk')
                            ->icon('heroicon-o-pencil-square')
                            ->url(fn (Product $record): string => EditProduct::getUrl(['record' => $record])),
                    ]),
            ])
            ->emptyStateHeading('Semua Stok Aman')
            ->emptyStateDescription('Tidak ada produk yang berada di bawah ambang batas minimum.')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->paginated([5]);
    }
}
