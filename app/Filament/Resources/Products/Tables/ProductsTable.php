<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Warehouse;
use App\Services\StockService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction('preview')
            ->recordUrl(null)
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('base_unit')
                    ->label('Satuan')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('stockLevels.on_hand')
                    ->label('Stok Fisik')
                    ->state(function (Product $record) {
                        return $record->stockLevels->sum('on_hand');
                    })
                    ->sortable(),
                TextColumn::make('stockLevels.reserved')
                    ->label('Di-Hold')
                    ->state(function (Product $record) {
                        return $record->stockLevels->sum('reserved');
                    })
                    ->color('warning'),
                TextColumn::make('available_stock')
                    ->label('Stok Tersedia')
                    ->state(function (Product $record) {
                        $onHand = $record->stockLevels->sum('on_hand');
                        $reserved = $record->stockLevels->sum('reserved');

                        return max(0, $onHand - $reserved);
                    })
                    ->badge()
                    ->color(function (Product $record) {
                        $available = max(0, $record->stockLevels->sum('on_hand') - $record->stockLevels->sum('reserved'));
                        if ($available <= 0) {
                            return 'danger';
                        }
                        if ($available <= $record->min_stock) {
                            return 'warning';
                        }

                        return 'success';
                    }),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                IconColumn::make('show_price_public')
                    ->label('Publik')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Lihat Rincian')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (Product $record) => "Rincian & Status Stok: {$record->name}")
                    ->modalWidth(Width::FourExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->extraModalFooterActions([
                        Action::make('editProduct')
                            ->label('Edit Produk Ini')
                            ->icon('heroicon-o-pencil-square')
                            ->color('primary')
                            ->url(fn (Product $record) => EditProduct::getUrl(['record' => $record])),
                    ])
                    ->modalContent(fn (Product $record) => view('filament.resources.products.preview-modal', [
                        'product' => $record->loadMissing(['category', 'units', 'prices.unit', 'stockLevels', 'stockBatches']),
                    ])),
                Action::make('adjustStock')
                    ->label('Penyesuaian Stok')
                    ->icon('heroicon-o-arrows-up-down')
                    ->color('warning')
                    ->modalHeading(fn (Product $record) => "Penyesuaian Stok: {$record->name}")
                    ->modalDescription(function (Product $record) {
                        $onHand = $record->stockLevels->sum('on_hand');
                        $reserved = $record->stockLevels->sum('reserved');
                        $available = max(0, $onHand - $reserved);

                        return "Stok Fisik: {$onHand} {$record->base_unit} | Di-Hold: {$reserved} {$record->base_unit} | Tersedia: {$available} {$record->base_unit}";
                    })
                    ->form([
                        Select::make('warehouse_id')
                            ->label('Gudang')
                            ->options(fn () => Warehouse::pluck('name', 'id'))
                            ->default(1)
                            ->required(),
                        Radio::make('adjustment_type')
                            ->label('Jenis Penyesuaian')
                            ->options([
                                'decrease' => 'Kurangi Stok (Rusak / Kadaluarsa / Hadiah / Sample / Pemakaian Sendiri)',
                                'increase' => 'Tambah Stok (Koreksi Lebih / Temuan Fisik)',
                                'set' => 'Setel Total Fisik Baru (Opname Riil)',
                            ])
                            ->default('decrease')
                            ->required()
                            ->live(),
                        Select::make('reason_category')
                            ->label('Alasan / Keperluan')
                            ->options([
                                'Barang Rusak / Pecah' => 'Barang Rusak / Pecah / Cacat',
                                'Barang Kadaluarsa' => 'Barang Kadaluarsa (Expired)',
                                'Hadiah / Promo / Gift' => 'Hadiah Promosi / Bonus / Gift (Gratis)',
                                'Sampel Produk' => 'Sampel Produk (Testing / Tester)',
                                'Pemakaian Internal' => 'Pemakaian Internal Toko / Gudang',
                                'Koreksi Selisih Opname' => 'Koreksi Selisih Opname Fisik',
                                'Lainnya' => 'Alasan Lainnya',
                            ])
                            ->default('Barang Rusak / Pecah')
                            ->visible(fn ($get) => in_array($get('adjustment_type'), ['decrease', 'increase']))
                            ->required(),
                        Select::make('unit_id')
                            ->label('Satuan')
                            ->options(function (Product $record) {
                                return $record->units()->pluck('unit_name', 'id');
                            })
                            ->default(function (Product $record) {
                                return $record->units()->where('conversion_to_base', 1)->value('id')
                                    ?? $record->units()->first()?->id;
                            })
                            ->required(),
                        TextInput::make('qty')
                            ->label(fn ($get) => $get('adjustment_type') === 'set' ? 'Jumlah Total Stok Fisik Baru' : 'Jumlah yang Disesuaikan')
                            ->numeric()
                            ->minValue(fn ($get) => $get('adjustment_type') === 'set' ? 0 : 1)
                            ->required(),
                        Textarea::make('notes')
                            ->label('Keterangan Tambahan (Opsional)')
                            ->placeholder('Contoh: Bocor 2 botol di rak / Diberikan tester ke reseller')
                            ->rows(2),
                    ])
                    ->action(function (Product $record, array $data) {
                        $stockService = app(StockService::class);
                        $warehouseId = (int) $data['warehouse_id'];
                        $unit = ProductUnit::find($data['unit_id']);
                        $conversion = $unit ? $unit->conversion_to_base : 1;
                        $qtyBase = (int) $data['qty'] * $conversion;

                        $level = DB::table('stock_levels')
                            ->where('product_id', $record->id)
                            ->where('warehouse_id', $warehouseId)
                            ->first();

                        $currentOnHand = $level ? (int) $level->on_hand : 0;
                        $currentReserved = $level ? (int) $level->reserved : 0;

                        $type = $data['adjustment_type'];
                        $newQty = match ($type) {
                            'decrease' => $currentOnHand - $qtyBase,
                            'increase' => $currentOnHand + $qtyBase,
                            'set' => (int) $data['qty'] * $conversion,
                        };

                        if ($newQty < 0) {
                            Notification::make()
                                ->title('Gagal: Pengurangan Melebihi Stok Fisik')
                                ->body("Stok fisik saat ini hanya {$currentOnHand} {$record->base_unit}. Tidak dapat mengurangi sebanyak {$qtyBase} {$record->base_unit}.")
                                ->danger()
                                ->send();

                            return;
                        }

                        if ($newQty < $currentReserved) {
                            Notification::make()
                                ->title('Gagal: Menabrak Kuota Stok Di-Hold')
                                ->body("Terdapat {$currentReserved} {$record->base_unit} yang sedang di-hold pesanan pembeli. Stok fisik akhir ({$newQty}) tidak boleh lebih kecil dari kuota hold.")
                                ->danger()
                                ->send();

                            return;
                        }

                        $reason = trim(($data['reason_category'] ?? 'Penyesuaian Stok').($data['notes'] ? ' - '.$data['notes'] : ''));

                        try {
                            $stockService->adjust(
                                productId: $record->id,
                                warehouseId: $warehouseId,
                                newQty: $newQty,
                                reason: $reason,
                                userId: auth()->id()
                            );

                            Notification::make()
                                ->title('Penyesuaian Stok Berhasil')
                                ->body("Stok {$record->name} berhasil diperbarui dari {$currentOnHand} menjadi {$newQty} {$record->base_unit}.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal Melakukan Penyesuaian')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
