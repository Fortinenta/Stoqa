<?php

use App\Models\Category;
use App\Models\CustomerPrice;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\User;
use App\Services\PricingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->calculator = app(PricingCalculator::class);

    $this->category = Category::create(['name' => 'Snack']);
    $this->product = Product::create([
        'sku' => 'SNK-KERIPIK',
        'name' => 'Keripik Singkong Balado',
        'category_id' => $this->category->id,
        'base_unit' => 'pcs',
        'min_stock' => 10,
        'is_active' => true,
    ]);

    // Satuan:
    // pcs (dasar = 1)
    // pack (1 pack = 12 pcs)
    // karton (1 karton = 120 pcs)
    $this->unitPcs = ProductUnit::create([
        'product_id' => $this->product->id,
        'unit_name' => 'pcs',
        'conversion_to_base' => 1,
    ]);

    $this->unitPack = ProductUnit::create([
        'product_id' => $this->product->id,
        'unit_name' => 'pack',
        'conversion_to_base' => 12,
    ]);

    $this->unitKarton = ProductUnit::create([
        'product_id' => $this->product->id,
        'unit_name' => 'karton',
        'conversion_to_base' => 120,
    ]);

    // Tier Harga:
    // Ecer: min 1 pcs @ Rp15.000
    // Pack: min 1 pack @ Rp165.000 (Rp13.750/pcs)
    // Grosir: min 1 karton @ Rp1.500.000 (Rp12.500/pcs)
    ProductPrice::create([
        'product_id' => $this->product->id,
        'unit_id' => $this->unitPcs->id,
        'tier_name' => 'Ecer',
        'min_qty' => 1,
        'price' => 15000,
    ]);

    ProductPrice::create([
        'product_id' => $this->product->id,
        'unit_id' => $this->unitPack->id,
        'tier_name' => 'Pack',
        'min_qty' => 1,
        'price' => 165000,
    ]);

    ProductPrice::create([
        'product_id' => $this->product->id,
        'unit_id' => $this->unitKarton->id,
        'tier_name' => 'Grosir/Karton',
        'min_qty' => 1,
        'price' => 1500000,
    ]);
});

test('menghitung harga ecer untuk pembelian satuan kecil', function () {
    // Beli 3 pcs @ Rp15.000 = Rp45.000
    $result = $this->calculator->calculateBestPrice($this->product, 3);

    expect($result['total_price'])->toEqual(45000.0)
        ->and($result['price_source'])->toBe('tier');
});

test('memecah order campuran ke satuan terbesar dengan total termurah', function () {
    // 1 karton (120 pcs) + 2 pack (24 pcs) + 5 pcs ecer = 149 pcs
    // 1 karton @ 1.500.000 + 2 pack @ 165.000 (330.000) + 5 pcs @ 15.000 (75.000) = Rp1.905.000
    $result = $this->calculator->calculateBestPrice($this->product, 149);

    expect($result['total_price'])->toEqual(1905000.0)
        ->and($result['breakdown'])->toHaveCount(3);
});

test('memilih paket lebih hemat jika pembulatan ke satuan lebih besar menghasilkan total lebih murah', function () {
    // 11 pcs ecer = 11 x 15.000 = 165.000
    // 1 pack (12 pcs) = 165.000 (harga sama dapat 12 pcs, atau jika pack diskon)
    // Uji kasus beli 11 pcs jika pack @ 160.000:
    ProductPrice::where('unit_id', $this->unitPack->id)->update(['price' => 150000]);

    // 11 pcs ecer = 165.000, tapi 1 pack = 150.000 (lebih murah dapat 12 pcs!)
    $result = $this->calculator->calculateBestPrice($this->product, 11);

    expect($result['total_price'])->toEqual(150000.0)
        ->and($result['applied_tier'])->toBe('Pack');
});

test('menerapkan harga khusus pelanggan langganan yang mengesampingkan tier standar', function () {
    $customer = User::create([
        'name' => 'Toko Barokah',
        'email' => 'barokah@example.com',
        'password' => bcrypt('secret'),
    ]);

    // Toko Barokah dapat harga VIP khusus: Rp11.000/pcs untuk pembelian minimal 10 pcs
    CustomerPrice::create([
        'user_id' => $customer->id,
        'product_id' => $this->product->id,
        'unit_id' => $this->unitPcs->id,
        'min_qty' => 10,
        'price' => 11000,
    ]);

    // Pembelian 50 pcs oleh pelanggan biasa vs Toko Barokah
    $guestResult = $this->calculator->calculateBestPrice($this->product, 50, null);
    $vipResult = $this->calculator->calculateBestPrice($this->product, 50, $customer);

    // Pelanggan biasa: 4 pack (48 pcs @ Rp165.000 = Rp660.000) + 2 ecer (Rp30.000) = Rp690.000
    // Toko Barokah: 50 pcs @ Rp11.000 = Rp550.000
    expect($vipResult['total_price'])->toEqual(550000.0)
        ->and($vipResult['price_source'])->toBe('customer')
        ->and($vipResult['total_price'])->toBeLessThan($guestResult['total_price']);
});
