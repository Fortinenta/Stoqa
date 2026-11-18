<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // 1. Informasi Utama Produk (Full Width)
                Section::make('Informasi Utama Produk')
                    ->description('Identitas produk, SKU, kategori, dan batas peringatan stok menipis.')
                    ->icon('heroicon-o-tag')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('sku')
                            ->label('SKU / Kode Unik Produk')
                            ->placeholder('Contoh: BRS-PW5 / IDM-GOR')
                            ->unique(ignoreRecord: true)
                            ->maxLength(64)
                            ->required(),
                        TextInput::make('name')
                            ->label('Nama Produk Lengkap')
                            ->placeholder('Contoh: Beras Pandan Wangi Cianjur 5kg')
                            ->maxLength(255)
                            ->required(),
                        Select::make('category_id')
                            ->label('Kategori Produk')
                            ->placeholder('-- Pilih Kategori --')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nama Kategori')
                                    ->maxLength(100)
                                    ->required(),
                            ]),
                        TextInput::make('base_unit')
                            ->label('Satuan Dasar Fisik (Terkecil)')
                            ->placeholder('Contoh: pcs, pouch, bungkus, botol, sachet')
                            ->maxLength(20)
                            ->default('pcs')
                            ->helperText('Satuan terkecil yang digunakan untuk perhitungan persediaan & FIFO')
                            ->required(),
                        TextInput::make('min_stock')
                            ->label('Ambang Batas Stok Menipis')
                            ->numeric()
                            ->minValue(0)
                            ->default(10)
                            ->helperText('Peringatan akan muncul jika stok di bawah angka ini')
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->helperText('Jika non-aktif, produk tidak tampil di katalog belanja')
                            ->default(true),
                        Toggle::make('show_price_public')
                            ->label('Tampilkan Harga ke Pengunjung Publik (Tanpa Login)')
                            ->default(true),
                        Textarea::make('description')
                            ->label('Deskripsi Produk Lengkap')
                            ->placeholder('Tuliskan spesifikasi, keunggulan, atau petunjuk penyimpanan')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(['sm' => 1, 'md' => 2]),

                // 2. Satuan Penjualan & Konversi (Full Width)
                Section::make('Satuan Penjualan Tambahan & Konversi')
                    ->description('Daftarkan kemasan yang lebih besar (misal: Pack, Dus, Karton) beserta nilai konversinya ke satuan dasar.')
                    ->icon('heroicon-o-arrows-pointing-out')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('units')
                            ->relationship('units')
                            ->label('Daftar Satuan Kemasan')
                            ->addActionLabel('+ Tambah Satuan Kemasan')
                            ->schema([
                                TextInput::make('unit_name')
                                    ->label('Nama Satuan Kemasan')
                                    ->placeholder('Contoh: Dus (40 Bungkus) / Karton (12 Pcs) / Ball (5 Karung)')
                                    ->maxLength(50)
                                    ->required()
                                    ->columnSpan(['sm' => 12, 'md' => 7]),

                                TextInput::make('conversion_to_base')
                                    ->label('Jumlah Satuan Dasar per Satuan Ini')
                                    ->placeholder('Contoh: 40')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->helperText('Berapa isi satuan dasar di dalam kemasan ini')
                                    ->required()
                                    ->columnSpan(['sm' => 12, 'md' => 5]),
                            ])
                            ->columns(12)
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),

                // 3. Tier Harga Bertingkat (Full Width, Lega & Anti-Terpotong)
                Section::make('Tier Harga Bertingkat (Diskon Volume)')
                    ->description('Tentukan harga jual berdasarkan kuantitas beli minimum untuk ecer maupun grosir.')
                    ->icon('heroicon-o-currency-dollar')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('prices')
                            ->relationship('prices')
                            ->label('Daftar Tier Harga')
                            ->addActionLabel('+ Tambah Tier Harga')
                            ->schema([
                                TextInput::make('tier_name')
                                    ->label('Nama Tier')
                                    ->placeholder('Contoh: Ecer 1-5 / Dus Grosir 5+')
                                    ->maxLength(50)
                                    ->required()
                                    ->columnSpan(['sm' => 12, 'md' => 3]),

                                Select::make('unit_id')
                                    ->label('Satuan')
                                    ->placeholder('-- Pilih Satuan --')
                                    ->options(function ($record, $livewire) {
                                        $product = null;
                                        if (is_object($livewire) && method_exists($livewire, 'getRecord')) {
                                            $product = $livewire->getRecord();
                                        } elseif (isset($livewire->record) && $livewire->record instanceof Product) {
                                            $product = $livewire->record;
                                        } elseif ($record instanceof ProductPrice) {
                                            $product = $record->product;
                                        }

                                        if ($product instanceof Product && $product->exists) {
                                            return $product->units()->pluck('unit_name', 'id');
                                        }

                                        return ProductUnit::pluck('unit_name', 'id');
                                    })
                                    ->searchable()
                                    ->nullable()
                                    ->columnSpan(['sm' => 12, 'md' => 3]),

                                TextInput::make('min_qty')
                                    ->label('Jumlah Minimum')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->columnSpan(['sm' => 6, 'md' => 3]),

                                TextInput::make('price')
                                    ->label('Harga Jual')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->required()
                                    ->columnSpan(['sm' => 6, 'md' => 3]),
                            ])
                            ->columns(12)
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
