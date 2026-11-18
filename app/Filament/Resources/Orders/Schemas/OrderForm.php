<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Product;
use App\Models\ProductUnit;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // 1. Informasi Header Order (Full Width)
                Section::make('Informasi Pesanan & Pelanggan')
                    ->description('Lengkapi identitas pembeli, gudang pengeluaran, dan catatan pengiriman.')
                    ->icon('heroicon-o-user-circle')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('user_id')
                            ->label('Akun Pelanggan (Jika Terdaftar)')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih akun terdaftar atau kosongkan jika pembeli umum'),
                        TextInput::make('customer_name')
                            ->label('Nama Pembeli (Jika Tanpa Akun)')
                            ->placeholder('Contoh: Budi Santoso / Toko Sumber Makmur')
                            ->maxLength(255),
                        Select::make('warehouse_id')
                            ->label('Gudang Pengeluaran')
                            ->relationship('warehouse', 'name')
                            ->default(1)
                            ->required(),
                        Select::make('channel')
                            ->label('Saluran Penjualan')
                            ->options([
                                'manual' => 'Penjualan Langsung (Manual / Kasir Gudang)',
                                'catalog' => 'Katalog Web Online',
                            ])
                            ->default('manual')
                            ->required(),
                        Textarea::make('note')
                            ->label('Catatan Order / Pengiriman')
                            ->placeholder('Contoh: Titip di pos satpam / packing ekstra buble wrap')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 2]),

                // 2. Daftar Produk yang Dijual (Full Width, Lega & Anti-Terpotong)
                Section::make('Daftar Produk yang Dijual')
                    ->description('Masukkan barang, satuan kemasan, jumlah pesanan, dan harga jual.')
                    ->icon('heroicon-o-shopping-bag')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->label('Item Barang')
                            ->addActionLabel('+ Tambah Produk Pesanan')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Pilih Produk')
                                    ->placeholder('-- Pilih Produk --')
                                    ->options(Product::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('unit_id', null))
                                    ->required()
                                    ->columnSpan(['sm' => 12, 'md' => 5]),

                                Select::make('unit_id')
                                    ->label('Satuan')
                                    ->placeholder('-- Pilih Satuan --')
                                    ->options(function (callable $get) {
                                        $productId = $get('product_id');
                                        if (! $productId) {
                                            return [];
                                        }

                                        return ProductUnit::where('product_id', $productId)->pluck('unit_name', 'id');
                                    })
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(['sm' => 12, 'md' => 3]),

                                TextInput::make('qty')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->reactive()
                                    ->required()
                                    ->columnSpan(['sm' => 6, 'md' => 2]),

                                TextInput::make('unit_price')
                                    ->label('Harga per Satuan')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->required()
                                    ->columnSpan(['sm' => 6, 'md' => 2]),
                            ])
                            ->columns(12)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->columnSpanFull(),
                    ]),

                // 3. Biaya Operasional & Pengiriman (Di Bawah Line Items, Rapi & Simetris)
                Section::make('Biaya Pengiriman & Operasional Tambahan')
                    ->description('Biaya yang ditagihkan untuk ongkir dan penanganan (dihitung ke total tagihan).')
                    ->icon('heroicon-o-truck')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('shipping_cost')
                            ->label('Ongkir yang Ditagihkan ke Pembeli')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('Rp')
                            ->helperText('Diisi biaya ekspedisi atau kurir toko'),
                        TextInput::make('other_cost')
                            ->label('Biaya Lain Ditagihkan')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('Rp')
                            ->helperText('Biaya asuransi, packing kayu, atau penanganan khusus'),
                    ])
                    ->columns(['sm' => 1, 'md' => 2]),
            ]);
    }
}
