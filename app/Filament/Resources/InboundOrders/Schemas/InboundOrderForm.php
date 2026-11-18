<?php

namespace App\Filament\Resources\InboundOrders\Schemas;

use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InboundOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // 1. Informasi Header Penerimaan Barang (Full Width)
                Section::make('Informasi Penerimaan Barang')
                    ->description('Catat identitas supplier, gudang tujuan, tanggal kedatangan fisik, dan biaya masuk.')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('supplier_id')
                            ->label('Supplier / Pemasok')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('-- Pilih Supplier --'),
                        Select::make('warehouse_id')
                            ->label('Gudang Tujuan Masuk')
                            ->relationship('warehouse', 'name')
                            ->default(1)
                            ->required(),
                        DatePicker::make('received_date')
                            ->label('Tanggal Terima Fisik')
                            ->default(now())
                            ->required(),
                        TextInput::make('shipping_cost')
                            ->label('Ongkos Kirim Masuk (Landed Cost)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('Rp')
                            ->helperText('Akan dialokasikan proporsional ke HPP barang')
                            ->required(),
                        TextInput::make('other_cost')
                            ->label('Biaya Lain Masuk (Bongkar Muat, Asuransi)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('Rp')
                            ->helperText('Akan dialokasikan proporsional ke HPP barang')
                            ->required(),
                        Textarea::make('note')
                            ->label('Catatan Penerimaan')
                            ->placeholder('Nomor surat jalan supplier / keterangan kondisi truk ekspedisi')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3]),

                // 2. Daftar Produk Masuk (Full Width, Lega & Anti-Terpotong)
                Section::make('Daftar Produk Masuk')
                    ->description('Masukkan rincian kuantitas fisik dan harga beli awal per satuan dasar.')
                    ->icon('heroicon-o-cube')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->label('Item Barang Masuk')
                            ->addActionLabel('+ Tambah Barang Masuk')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Pilih Produk')
                                    ->placeholder('-- Pilih Produk --')
                                    ->relationship('items.product', 'name')
                                    ->options(Product::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(['sm' => 12, 'md' => 6]),

                                TextInput::make('qty_base')
                                    ->label('Jumlah (Satuan Dasar)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->columnSpan(['sm' => 6, 'md' => 3]),

                                TextInput::make('unit_price')
                                    ->label('Harga Beli per Satuan Dasar')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->required()
                                    ->columnSpan(['sm' => 6, 'md' => 3]),
                            ])
                            ->columns(12)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
