<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\CustomerPrice;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ReservationService;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::first() ?? Warehouse::create([
            'id' => 1,
            'name' => 'Gudang Utama',
            'code' => 'WH-UTAMA',
            'is_default' => true,
        ]);

        // 1. Roles & Users
        $roleCustomer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $roleStaf = Role::firstOrCreate(['name' => 'staf_gudang', 'guard_name' => 'web']);

        $staf = User::firstOrCreate(
            ['email' => 'staf@example.com'],
            [
                'name' => 'Staf Gudang Anto',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $staf->assignRole($roleStaf);

        $customerBudi = User::firstOrCreate(
            ['email' => 'budi@example.com'],
            [
                'name' => 'Budi Santoso',
                'phone' => '081234567890',
                'address' => 'Jl. Cempaka Putih Barat No. 12, RT 04/RW 03, Jakarta Pusat 10520',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $customerBudi->assignRole($roleCustomer);

        $customerToko = User::firstOrCreate(
            ['email' => 'toko.berkah@example.com'],
            [
                'name' => 'Toko Berkah Jaya',
                'phone' => '085678901234',
                'address' => 'Pasar Induk Kramat Jati Blok C No. 4, Jakarta Timur 13540',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $customerToko->assignRole($roleCustomer);

        $customerRatna = User::firstOrCreate(
            ['email' => 'ratna@example.com'],
            [
                'name' => 'Ibu Ratna (Warung Barokah)',
                'phone' => '081399887766',
                'address' => 'Jl. Tebet Timur Dalam Raya No. 45, Jakarta Selatan 12820',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $customerRatna->assignRole($roleCustomer);

        $customerHendra = User::firstOrCreate(
            ['email' => 'hendra@example.com'],
            [
                'name' => 'Bapak Hendra (Kantin Sejahtera)',
                'phone' => '081288776655',
                'address' => 'Kawasan Industri Pulogadung Blok B No. 8, Jakarta Timur',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $customerHendra->assignRole($roleCustomer);

        // 2. Suppliers
        $supIndofood = Supplier::firstOrCreate(
            ['name' => 'PT Indofood Sukses Makmur'],
            ['contact' => '021-57958822', 'address' => 'Sudirman Plaza, Jakarta']
        );
        $supWilmar = Supplier::firstOrCreate(
            ['name' => 'PT Wilmar Nabati Indonesia'],
            ['contact' => '021-29938888', 'address' => 'Multivision Tower, Jakarta']
        );
        $supFood = Supplier::firstOrCreate(
            ['name' => 'Distributor Sembako Nusantara'],
            ['contact' => '021-88997766', 'address' => 'Kawasan Industri Pulo Gadung, Jakarta']
        );
        $supUnilever = Supplier::firstOrCreate(
            ['name' => 'PT Unilever Indonesia Tbk'],
            ['contact' => '021-5262112', 'address' => 'Green Office Park BSD, Tangerang']
        );
        $supFrisian = Supplier::firstOrCreate(
            ['name' => 'PT Frisian Flag Indonesia'],
            ['contact' => '021-8778899', 'address' => 'Jl. Raya Bogor KM 5, Ciracas, Jakarta Timur']
        );

        // 3. Kategori Produk
        $catMinyak = Category::firstOrCreate(['slug' => 'minyak-goreng'], ['name' => 'Minyak Goreng']);
        $catBeras = Category::firstOrCreate(['slug' => 'beras-tepung'], ['name' => 'Beras & Tepung']);
        $catGula = Category::firstOrCreate(['slug' => 'gula-kopi-teh'], ['name' => 'Gula & Kopi']);
        $catMi = Category::firstOrCreate(['slug' => 'mi-instan'], ['name' => 'Mi Instan']);
        $catBumbu = Category::firstOrCreate(['slug' => 'bumbu-dapur'], ['name' => 'Kecap & Bumbu Dapur']);
        $catSusu = Category::firstOrCreate(['slug' => 'susu-olahan'], ['name' => 'Susu & Olahan']);

        // 4. Master Produk & Units & Pricing
        // Produk 1: Minyak Sania 2L (Stok Aman: 120 - 12 hold = 108 Tersedia, Min: 15)
        $prodSania = Product::firstOrCreate(['sku' => 'SNI-2L'], [
            'category_id' => $catMinyak->id,
            'name' => 'Minyak Goreng Sania 2 Liter',
            'base_unit' => 'Pouch',
            'description' => 'Minyak goreng kelapa sawit berkualitas tinggi dengan vitamin E.',
            'min_stock' => 15,
            'show_price_public' => true,
            'is_active' => true,
        ]);
        $unitSaniaPouch = ProductUnit::firstOrCreate(['product_id' => $prodSania->id, 'unit_name' => 'Pouch'], ['conversion_to_base' => 1]);
        $unitSaniaDus = ProductUnit::firstOrCreate(['product_id' => $prodSania->id, 'unit_name' => 'Dus (6 Pouch)'], ['conversion_to_base' => 6]);
        ProductPrice::firstOrCreate(['product_id' => $prodSania->id, 'unit_id' => $unitSaniaPouch->id, 'min_qty' => 1], ['price' => 36000, 'tier_name' => 'Ecer 1-5 Pouch']);
        ProductPrice::firstOrCreate(['product_id' => $prodSania->id, 'unit_id' => $unitSaniaPouch->id, 'min_qty' => 6], ['price' => 34500, 'tier_name' => 'Grosir 6+ Pouch']);
        ProductPrice::firstOrCreate(['product_id' => $prodSania->id, 'unit_id' => $unitSaniaDus->id, 'min_qty' => 1], ['price' => 205000, 'tier_name' => 'Dus 1-4 Karton']);
        ProductPrice::firstOrCreate(['product_id' => $prodSania->id, 'unit_id' => $unitSaniaDus->id, 'min_qty' => 5], ['price' => 200000, 'tier_name' => 'Dus 5+ Karton']);

        // Produk 2: Beras Pandan Wangi 5kg (Stok Aman: 50 - 1 = 49 Tersedia, Min: 10)
        $prodBeras = Product::firstOrCreate(['sku' => 'BRS-PW5'], [
            'category_id' => $catBeras->id,
            'name' => 'Beras Pandan Wangi Cianjur 5kg',
            'base_unit' => 'Karung',
            'description' => 'Beras aromatik pulen asli Cianjur tanpa pemutih & pengawet.',
            'min_stock' => 10,
            'show_price_public' => true,
            'is_active' => true,
        ]);
        $unitBerasKarung = ProductUnit::firstOrCreate(['product_id' => $prodBeras->id, 'unit_name' => 'Karung'], ['conversion_to_base' => 1]);
        $unitBerasBall = ProductUnit::firstOrCreate(['product_id' => $prodBeras->id, 'unit_name' => 'Ball (5 Karung)'], ['conversion_to_base' => 5]);
        ProductPrice::firstOrCreate(['product_id' => $prodBeras->id, 'unit_id' => $unitBerasKarung->id, 'min_qty' => 1], ['price' => 75000, 'tier_name' => 'Ecer 1-4 Karung']);
        ProductPrice::firstOrCreate(['product_id' => $prodBeras->id, 'unit_id' => $unitBerasKarung->id, 'min_qty' => 5], ['price' => 72000, 'tier_name' => 'Grosir 5+ Karung']);
        ProductPrice::firstOrCreate(['product_id' => $prodBeras->id, 'unit_id' => $unitBerasBall->id, 'min_qty' => 1], ['price' => 355000, 'tier_name' => 'Ball Grosir']);

        // Produk 3: Gulaku Kuning 1kg (Stok Aman: 200 - 45 = 155 Tersedia, Min: 20)
        $prodGula = Product::firstOrCreate(['sku' => 'GLK-1KG'], [
            'category_id' => $catGula->id,
            'name' => 'Gulaku Gula Tebu Premium Kuning 1kg',
            'base_unit' => 'Bungkus',
            'description' => 'Gula pasir tebu alami dengan rasa manis legit alami.',
            'min_stock' => 20,
            'show_price_public' => true,
            'is_active' => true,
        ]);
        $unitGulaBks = ProductUnit::firstOrCreate(['product_id' => $prodGula->id, 'unit_name' => 'Bungkus'], ['conversion_to_base' => 1]);
        $unitGulaDus = ProductUnit::firstOrCreate(['product_id' => $prodGula->id, 'unit_name' => 'Karton (20 Bungkus)'], ['conversion_to_base' => 20]);
        ProductPrice::firstOrCreate(['product_id' => $prodGula->id, 'unit_id' => $unitGulaBks->id, 'min_qty' => 1], ['price' => 17500, 'tier_name' => 'Ecer']);
        ProductPrice::firstOrCreate(['product_id' => $prodGula->id, 'unit_id' => $unitGulaBks->id, 'min_qty' => 10], ['price' => 16800, 'tier_name' => 'Grosir 10+ Bks']);
        ProductPrice::firstOrCreate(['product_id' => $prodGula->id, 'unit_id' => $unitGulaDus->id, 'min_qty' => 1], ['price' => 330000, 'tier_name' => 'Karton']);

        // Produk 4: Indomie Goreng Original 85g (Stok Aman: 400 - 120 = 280 Tersedia, Min: 40)
        $prodIndomie = Product::firstOrCreate(['sku' => 'IDM-GOR'], [
            'category_id' => $catMi->id,
            'name' => 'Indomie Mi Goreng Spesial 85g',
            'base_unit' => 'Bungkus',
            'description' => 'Mi instan goreng favorit Indonesia dengan bumbu gurih lengkap.',
            'min_stock' => 40,
            'show_price_public' => true,
            'is_active' => true,
        ]);
        $unitMiBks = ProductUnit::firstOrCreate(['product_id' => $prodIndomie->id, 'unit_name' => 'Bungkus'], ['conversion_to_base' => 1]);
        $unitMiDus = ProductUnit::firstOrCreate(['product_id' => $prodIndomie->id, 'unit_name' => 'Dus (40 Bungkus)'], ['conversion_to_base' => 40]);
        ProductPrice::firstOrCreate(['product_id' => $prodIndomie->id, 'unit_id' => $unitMiBks->id, 'min_qty' => 1], ['price' => 3100, 'tier_name' => 'Ecer Satuan']);
        ProductPrice::firstOrCreate(['product_id' => $prodIndomie->id, 'unit_id' => $unitMiDus->id, 'min_qty' => 1], ['price' => 116000, 'tier_name' => 'Dus 1-4']);
        ProductPrice::firstOrCreate(['product_id' => $prodIndomie->id, 'unit_id' => $unitMiDus->id, 'min_qty' => 5], ['price' => 113000, 'tier_name' => 'Dus 5+']);

        // Harga Khusus Langganan untuk Toko Berkah Jaya
        CustomerPrice::firstOrCreate(
            ['user_id' => $customerToko->id, 'product_id' => $prodIndomie->id, 'unit_id' => $unitMiDus->id],
            ['price' => 111000, 'min_qty' => 1]
        );

        // Produk 5: Kopi Kapal Api Special Mix 24g (Stok Aman: 600 Tersedia, Min: 50)
        $prodKopi = Product::firstOrCreate(['sku' => 'KPL-MIX'], [
            'category_id' => $catGula->id,
            'name' => 'Kopi Kapal Api Special Mix 24g',
            'base_unit' => 'Sachet',
            'description' => 'Kopi hitam bubuk campur gula beraroma mantap.',
            'min_stock' => 50,
            'show_price_public' => true,
            'is_active' => true,
        ]);
        $unitKopiSch = ProductUnit::firstOrCreate(['product_id' => $prodKopi->id, 'unit_name' => 'Sachet'], ['conversion_to_base' => 1]);
        $unitKopiRcg = ProductUnit::firstOrCreate(['product_id' => $prodKopi->id, 'unit_name' => 'Renceng (10 Sachet)'], ['conversion_to_base' => 10]);
        $unitKopiDus = ProductUnit::firstOrCreate(['product_id' => $prodKopi->id, 'unit_name' => 'Karton (120 Sachet)'], ['conversion_to_base' => 120]);
        ProductPrice::firstOrCreate(['product_id' => $prodKopi->id, 'unit_id' => $unitKopiSch->id, 'min_qty' => 1], ['price' => 1500, 'tier_name' => 'Ecer Sachet']);
        ProductPrice::firstOrCreate(['product_id' => $prodKopi->id, 'unit_id' => $unitKopiRcg->id, 'min_qty' => 1], ['price' => 14000, 'tier_name' => 'Renceng']);
        ProductPrice::firstOrCreate(['product_id' => $prodKopi->id, 'unit_id' => $unitKopiDus->id, 'min_qty' => 1], ['price' => 160000, 'tier_name' => 'Karton Grosir']);

        // Produk 6: Tepung Terigu Segitiga Biru 1kg (Stok Aman: 120 Tersedia, Min: 20)
        $prodTerigu = Product::firstOrCreate(['sku' => 'TBN-1KG'], [
            'category_id' => $catBeras->id,
            'name' => 'Tepung Terigu Segitiga Biru Premium 1kg',
            'base_unit' => 'Pcs',
            'description' => 'Tepung terigu serbaguna protein sedang untuk aneka olahan.',
            'min_stock' => 20,
            'show_price_public' => true,
            'is_active' => true,
        ]);
        $unitTeriguPcs = ProductUnit::firstOrCreate(['product_id' => $prodTerigu->id, 'unit_name' => 'Pcs'], ['conversion_to_base' => 1]);
        $unitTeriguDus = ProductUnit::firstOrCreate(['product_id' => $prodTerigu->id, 'unit_name' => 'Karton (12 Pcs)'], ['conversion_to_base' => 12]);
        ProductPrice::firstOrCreate(['product_id' => $prodTerigu->id, 'unit_id' => $unitTeriguPcs->id, 'min_qty' => 1], ['price' => 13000, 'tier_name' => 'Ecer Pcs']);
        ProductPrice::firstOrCreate(['product_id' => $prodTerigu->id, 'unit_id' => $unitTeriguDus->id, 'min_qty' => 1], ['price' => 150000, 'tier_name' => 'Karton (12 Pcs)']);

        // -------------------------------------------------------------
        // PRODUK TAMBAHAN KHUSUS UNTUK PENGUJIAN STOK MENIPIS & HABIS:
        // -------------------------------------------------------------

        // Produk 7: SUSU KENTAL MANIS FRISIAN FLAG (STOK MENIPIS: 18 Tersedia <= Min 25)
        $prodSusu = Product::firstOrCreate(['sku' => 'FF-SKM'], [
            'category_id' => $catSusu->id,
            'name' => 'Susu Kental Manis Frisian Flag Gold 370g',
            'base_unit' => 'Kaleng',
            'description' => 'Susu kental manis bernutrisi tinggi dengan rasa gurih legit.',
            'min_stock' => 25,
            'show_price_public' => true,
            'is_active' => true,
        ]);
        $unitSusuKlg = ProductUnit::firstOrCreate(['product_id' => $prodSusu->id, 'unit_name' => 'Kaleng'], ['conversion_to_base' => 1]);
        $unitSusuDus = ProductUnit::firstOrCreate(['product_id' => $prodSusu->id, 'unit_name' => 'Karton (48 Kaleng)'], ['conversion_to_base' => 48]);
        ProductPrice::firstOrCreate(['product_id' => $prodSusu->id, 'unit_id' => $unitSusuKlg->id, 'min_qty' => 1], ['price' => 18000, 'tier_name' => 'Ecer Satuan']);
        ProductPrice::firstOrCreate(['product_id' => $prodSusu->id, 'unit_id' => $unitSusuKlg->id, 'min_qty' => 6], ['price' => 17200, 'tier_name' => 'Grosir 6+ Kaleng']);
        ProductPrice::firstOrCreate(['product_id' => $prodSusu->id, 'unit_id' => $unitSusuDus->id, 'min_qty' => 1], ['price' => 810000, 'tier_name' => 'Karton Pabrik']);

        // Produk 8: KECAP MANIS BANGO 520ML (STOK MENIPIS KARENA HOLD: Fisik 24, Hold 18 -> Tersedia 6 <= Min 20)
        $prodBango = Product::firstOrCreate(['sku' => 'BNG-520'], [
            'category_id' => $catBumbu->id,
            'name' => 'Kecap Manis Bango Refill Pouch 520ml',
            'base_unit' => 'Pouch',
            'description' => 'Kecap manis alami terbuat dari kedelai hitam Malika berkualitas.',
            'min_stock' => 20,
            'show_price_public' => true,
            'is_active' => true,
        ]);
        $unitBangoPch = ProductUnit::firstOrCreate(['product_id' => $prodBango->id, 'unit_name' => 'Pouch'], ['conversion_to_base' => 1]);
        $unitBangoDus = ProductUnit::firstOrCreate(['product_id' => $prodBango->id, 'unit_name' => 'Karton (12 Pouch)'], ['conversion_to_base' => 12]);
        ProductPrice::firstOrCreate(['product_id' => $prodBango->id, 'unit_id' => $unitBangoPch->id, 'min_qty' => 1], ['price' => 24500, 'tier_name' => 'Ecer Pouch']);
        ProductPrice::firstOrCreate(['product_id' => $prodBango->id, 'unit_id' => $unitBangoDus->id, 'min_qty' => 1], ['price' => 280000, 'tier_name' => 'Karton 12 Pouch']);

        // Produk 9: GARAM DAPUR CAP KAPAL (STOK HABIS TOTAL: Tersedia 0 <= Min 30)
        $prodGaram = Product::firstOrCreate(['sku' => 'GRM-KPL'], [
            'category_id' => $catBumbu->id,
            'name' => 'Garam Beryodium Cap Kapal 250g',
            'base_unit' => 'Bungkus',
            'description' => 'Garam dapur beryodium bersih murni untuk penyedap aneka hidangan.',
            'min_stock' => 30,
            'show_price_public' => true,
            'is_active' => true,
        ]);
        $unitGaramBks = ProductUnit::firstOrCreate(['product_id' => $prodGaram->id, 'unit_name' => 'Bungkus'], ['conversion_to_base' => 1]);
        $unitGaramPack = ProductUnit::firstOrCreate(['product_id' => $prodGaram->id, 'unit_name' => 'Paket (20 Bungkus)'], ['conversion_to_base' => 20]);
        ProductPrice::firstOrCreate(['product_id' => $prodGaram->id, 'unit_id' => $unitGaramBks->id, 'min_qty' => 1], ['price' => 3500, 'tier_name' => 'Ecer']);
        ProductPrice::firstOrCreate(['product_id' => $prodGaram->id, 'unit_id' => $unitGaramPack->id, 'min_qty' => 1], ['price' => 64000, 'tier_name' => 'Paket 20 Bks']);

        // Produk 10: MINYAK BIMOLI 2L (STOK MENIPIS HASIL OPNAME RUSAK: Fisik 8, Min 20)
        $prodBimoli = Product::firstOrCreate(['sku' => 'BML-2L'], [
            'category_id' => $catMinyak->id,
            'name' => 'Minyak Goreng Bimoli Klasik 2 Liter',
            'base_unit' => 'Pouch',
            'description' => 'Minyak goreng kelapa sawit murni dengan golden palm oil formula.',
            'min_stock' => 20,
            'show_price_public' => true,
            'is_active' => true,
        ]);
        $unitBimoliPch = ProductUnit::firstOrCreate(['product_id' => $prodBimoli->id, 'unit_name' => 'Pouch'], ['conversion_to_base' => 1]);
        $unitBimoliDus = ProductUnit::firstOrCreate(['product_id' => $prodBimoli->id, 'unit_name' => 'Dus (6 Pouch)'], ['conversion_to_base' => 6]);
        ProductPrice::firstOrCreate(['product_id' => $prodBimoli->id, 'unit_id' => $unitBimoliPch->id, 'min_qty' => 1], ['price' => 37000, 'tier_name' => 'Ecer Pouch']);
        ProductPrice::firstOrCreate(['product_id' => $prodBimoli->id, 'unit_id' => $unitBimoliDus->id, 'min_qty' => 1], ['price' => 212000, 'tier_name' => 'Dus 6 Pouch']);

        // 5. Inbound Stok (Barang Masuk) melalui StockService
        $stockService = app(StockService::class);
        $reservationService = app(ReservationService::class);

        // Inbound Batch Awal jika belum ada
        if (StockBatch::where('product_id', $prodSania->id)->count() === 0) {
            $stockService->receive([
                'supplier_id' => $supWilmar->id,
                'warehouse_id' => $warehouse->id,
                'received_date' => now()->subDays(6)->toDateString(),
                'shipping_cost' => 50000,
                'other_cost' => 0,
                'items' => [
                    ['product_id' => $prodSania->id, 'qty_base' => 120, 'unit_price' => 30500],
                ],
            ], $staf->id);
        }

        if (StockBatch::where('product_id', $prodIndomie->id)->count() === 0) {
            $stockService->receive([
                'supplier_id' => $supIndofood->id,
                'warehouse_id' => $warehouse->id,
                'received_date' => now()->subDays(5)->toDateString(),
                'shipping_cost' => 80000,
                'other_cost' => 20000,
                'items' => [
                    ['product_id' => $prodIndomie->id, 'qty_base' => 400, 'unit_price' => 2650],
                    ['product_id' => $prodTerigu->id, 'qty_base' => 120, 'unit_price' => 11000],
                ],
            ], $staf->id);
        }

        if (StockBatch::where('product_id', $prodBeras->id)->count() === 0) {
            $stockService->receive([
                'supplier_id' => $supFood->id,
                'warehouse_id' => $warehouse->id,
                'received_date' => now()->subDays(4)->toDateString(),
                'shipping_cost' => 120000,
                'other_cost' => 0,
                'items' => [
                    ['product_id' => $prodBeras->id, 'qty_base' => 50, 'unit_price' => 64000],
                    ['product_id' => $prodGula->id, 'qty_base' => 200, 'unit_price' => 14800],
                    ['product_id' => $prodKopi->id, 'qty_base' => 600, 'unit_price' => 1150],
                ],
            ], $staf->id);
        }

        // Inbound Produk-Produk Baru:
        if (StockBatch::where('product_id', $prodSusu->id)->count() === 0) {
            // Susu Kental Manis: Masuk hanya 18 kaleng (di bawah batas minimum 25)
            $stockService->receive([
                'supplier_id' => $supFrisian->id,
                'warehouse_id' => $warehouse->id,
                'received_date' => now()->subDays(3)->toDateString(),
                'shipping_cost' => 15000,
                'other_cost' => 0,
                'items' => [
                    ['product_id' => $prodSusu->id, 'qty_base' => 18, 'unit_price' => 15200],
                ],
            ], $staf->id);
        }

        if (StockBatch::where('product_id', $prodBango->id)->count() === 0) {
            // Kecap Bango: Masuk 24 pouch
            $stockService->receive([
                'supplier_id' => $supUnilever->id,
                'warehouse_id' => $warehouse->id,
                'received_date' => now()->subDays(2)->toDateString(),
                'shipping_cost' => 20000,
                'other_cost' => 0,
                'items' => [
                    ['product_id' => $prodBango->id, 'qty_base' => 24, 'unit_price' => 21000],
                ],
            ], $staf->id);
        }

        if (StockBatch::where('product_id', $prodBimoli->id)->count() === 0) {
            // Bimoli: Masuk 30 pouch, nanti kita kurangi 22 via opname rusak
            $stockService->receive([
                'supplier_id' => $supWilmar->id,
                'warehouse_id' => $warehouse->id,
                'received_date' => now()->subDays(4)->toDateString(),
                'shipping_cost' => 25000,
                'other_cost' => 0,
                'items' => [
                    ['product_id' => $prodBimoli->id, 'qty_base' => 30, 'unit_price' => 31000],
                ],
            ], $staf->id);

            // Jalankan Stock Opname Pengurangan (22 pouch rusak/bocor, sisa fisik = 8 pouch)
            $stockService->adjust(
                productId: $prodBimoli->id,
                warehouseId: $warehouse->id,
                newQty: 8,
                reason: 'Opname fisik: 22 pouch kemasan bocor/tertekan di gudang',
                userId: $staf->id
            );
        }

        // Garam Dapur: Pastikan stock_levels ada tapi on_hand = 0 (HABIS TOTAL)
        $stockService->ensureLevel($prodGaram->id, $warehouse->id);

        // 6. Dummy Orders untuk Simulasi Menyeluruh
        // ORDER A: Menunggu Ongkir dari Budi Santoso (Hold Stok Aktif!)
        $orderA = Order::firstOrCreate(
            ['invoice_no' => 'INV/2026/01/ORD-A'],
            [
                'user_id' => $customerBudi->id,
                'warehouse_id' => $warehouse->id,
                'channel' => 'catalog',
                'customer_name' => $customerBudi->name,
                'status' => OrderStatus::MENUNGGU_ONGKIR,
                'subtotal' => 526000,
                'shipping_cost' => 0,
                'other_cost' => 0,
                'total' => 526000,
                'payment_status' => 'unpaid',
                'shipping_due_at' => now()->addHours(3),
                'note' => 'Tolong dicek ongkir via kurir motor/mobil pickup ya min.',
                'created_at' => now()->subHours(1),
            ]
        );

        if ($orderA->items()->count() === 0) {
            $orderA->items()->createMany([
                [
                    'product_id' => $prodSania->id,
                    'unit_id' => $unitSaniaDus->id,
                    'qty' => 2,
                    'qty_base' => 12,
                    'unit_price' => 205000,
                    'price_source' => 'product_prices',
                    'line_total' => 410000,
                ],
                [
                    'product_id' => $prodIndomie->id,
                    'unit_id' => $unitMiDus->id,
                    'qty' => 1,
                    'qty_base' => 40,
                    'unit_price' => 116000,
                    'price_source' => 'product_prices',
                    'line_total' => 116000,
                ],
            ]);

            $reservationService->reserve(
                orderId: $orderA->id,
                warehouseId: $warehouse->id,
                items: [
                    ['product_id' => $prodSania->id, 'qty_base' => 12],
                    ['product_id' => $prodIndomie->id, 'qty_base' => 40],
                ],
                expiresAt: now()->addHours(3)
            );
        }

        // ORDER B: Menunggu Pembayaran dari Toko Berkah Jaya (Hold 40 Gula & 80 Indomie)
        $orderB = Order::firstOrCreate(
            ['invoice_no' => 'INV/2026/01/ORD-B'],
            [
                'user_id' => $customerToko->id,
                'warehouse_id' => $warehouse->id,
                'channel' => 'catalog',
                'customer_name' => $customerToko->name,
                'status' => OrderStatus::PENDING_PAYMENT,
                'subtotal' => 882000,
                'shipping_cost' => 35000,
                'other_cost' => 0,
                'total' => 917000,
                'payment_status' => 'unpaid',
                'invoiced_at' => now()->subHours(2),
                'expires_at' => now()->addHours(10),
                'note' => 'Dikirim pakai kurir Lalamove toko',
                'created_at' => now()->subHours(2),
            ]
        );

        if ($orderB->items()->count() === 0) {
            $orderB->items()->createMany([
                [
                    'product_id' => $prodGula->id,
                    'unit_id' => $unitGulaDus->id,
                    'qty' => 2,
                    'qty_base' => 40,
                    'unit_price' => 330000,
                    'price_source' => 'product_prices',
                    'line_total' => 660000,
                ],
                [
                    'product_id' => $prodIndomie->id,
                    'unit_id' => $unitMiDus->id,
                    'qty' => 2,
                    'qty_base' => 80,
                    'unit_price' => 111000,
                    'price_source' => 'customer_prices',
                    'line_total' => 222000,
                ],
            ]);

            $reservationService->reserve(
                orderId: $orderB->id,
                warehouseId: $warehouse->id,
                items: [
                    ['product_id' => $prodGula->id, 'qty_base' => 40],
                    ['product_id' => $prodIndomie->id, 'qty_base' => 80],
                ],
                expiresAt: now()->addHours(10)
            );
        }

        // ORDER C: Order Selesai & Lunas Kemarin (Stok terpotong FIFO)
        $orderC = Order::firstOrCreate(
            ['invoice_no' => 'INV/2026/01/ORD-C'],
            [
                'user_id' => $customerBudi->id,
                'warehouse_id' => $warehouse->id,
                'channel' => 'manual',
                'customer_name' => $customerBudi->name,
                'status' => OrderStatus::PAID,
                'subtotal' => 162500,
                'shipping_cost' => 15000,
                'other_cost' => 0,
                'total' => 177500,
                'payment_status' => 'paid',
                'invoiced_at' => now()->subDay(),
                'paid_at' => now()->subDay(),
                'note' => 'Pesanan diambil langsung di gudang',
                'created_at' => now()->subDay(),
            ]
        );

        if ($orderC->items()->count() === 0) {
            $itemC1 = $orderC->items()->create([
                'product_id' => $prodBeras->id,
                'unit_id' => $unitBerasKarung->id,
                'qty' => 1,
                'qty_base' => 1,
                'unit_price' => 75000,
                'price_source' => 'product_prices',
                'line_total' => 75000,
            ]);

            $itemC2 = $orderC->items()->create([
                'product_id' => $prodGula->id,
                'unit_id' => $unitGulaBks->id,
                'qty' => 5,
                'qty_base' => 5,
                'unit_price' => 17500,
                'price_source' => 'product_prices',
                'line_total' => 87500,
            ]);

            $stockService->fulfillFifoOutbound(
                warehouseId: $warehouse->id,
                items: [
                    ['product_id' => $prodBeras->id, 'qty_base' => 1, 'order_item_id' => $itemC1->id],
                    ['product_id' => $prodGula->id, 'qty_base' => 5, 'order_item_id' => $itemC2->id],
                ],
                refType: 'order',
                refId: $orderC->id,
                userId: $staf->id
            );
        }

        // ORDER D: Menahan Kuota Bango (18 Pouch) dari Ibu Ratna -> Menjadikan Stok Bango Kritis (Tersedia 6 <= 20)
        $orderD = Order::firstOrCreate(
            ['invoice_no' => 'INV/2026/01/ORD-D'],
            [
                'user_id' => $customerRatna->id,
                'warehouse_id' => $warehouse->id,
                'channel' => 'catalog',
                'customer_name' => $customerRatna->name,
                'status' => OrderStatus::MENUNGGU_ONGKIR,
                'subtotal' => 420000,
                'shipping_cost' => 0,
                'other_cost' => 0,
                'total' => 420000,
                'payment_status' => 'unpaid',
                'shipping_due_at' => now()->addHours(2),
                'note' => 'Langganan warung, minta dikirim sore',
                'created_at' => now()->subMinutes(40),
            ]
        );

        if ($orderD->items()->count() === 0) {
            $orderD->items()->create([
                'product_id' => $prodBango->id,
                'unit_id' => $unitBangoDus->id,
                'qty' => 1,
                'qty_base' => 12,
                'unit_price' => 280000,
                'price_source' => 'product_prices',
                'line_total' => 280000,
            ]);

            $orderD->items()->create([
                'product_id' => $prodBango->id,
                'unit_id' => $unitBangoPch->id,
                'qty' => 6,
                'qty_base' => 6,
                'unit_price' => 23333,
                'price_source' => 'product_prices',
                'line_total' => 140000,
            ]);

            // Kunci kuota 18 pouch kecap Bango!
            $reservationService->reserve(
                orderId: $orderD->id,
                warehouseId: $warehouse->id,
                items: [
                    ['product_id' => $prodBango->id, 'qty_base' => 18],
                ],
                expiresAt: now()->addHours(2)
            );
        }

        // ORDER E: Penjualan Grosir Selesai 3 Hari Lalu (Kantin Sejahtera) untuk memperkaya grafik 7 hari
        $orderE = Order::firstOrCreate(
            ['invoice_no' => 'INV/2026/01/ORD-E'],
            [
                'user_id' => $customerHendra->id,
                'warehouse_id' => $warehouse->id,
                'channel' => 'manual',
                'customer_name' => $customerHendra->name,
                'status' => OrderStatus::COMPLETED,
                'subtotal' => 840000,
                'shipping_cost' => 30000,
                'other_cost' => 0,
                'total' => 870000,
                'payment_status' => 'paid',
                'invoiced_at' => now()->subDays(3),
                'paid_at' => now()->subDays(3),
                'created_at' => now()->subDays(3),
                'cogs_total' => 690000,
                'note' => 'Pasokan mingguan kantin karyawan',
            ]
        );

        // ORDER F: Penjualan Online Sedang Dikirim 2 Hari Lalu
        $orderF = Order::firstOrCreate(
            ['invoice_no' => 'INV/2026/01/ORD-F'],
            [
                'user_id' => $customerBudi->id,
                'warehouse_id' => $warehouse->id,
                'channel' => 'catalog',
                'customer_name' => $customerBudi->name,
                'status' => OrderStatus::SHIPPED,
                'subtotal' => 450000,
                'shipping_cost' => 20000,
                'other_cost' => 0,
                'total' => 470000,
                'payment_status' => 'paid',
                'invoiced_at' => now()->subDays(2),
                'paid_at' => now()->subDays(2),
                'created_at' => now()->subDays(2),
                'cogs_total' => 370000,
                'note' => 'Resi kurir SiCepat: SCP-9908123',
            ]
        );

        // ORDER G: Penjualan Kasir Hari Ini (Order Cepat Tunai)
        $orderG = Order::firstOrCreate(
            ['invoice_no' => 'INV/2026/01/ORD-G'],
            [
                'user_id' => null,
                'warehouse_id' => $warehouse->id,
                'channel' => 'manual',
                'customer_name' => 'Pak Joko (Pembeli Langsung)',
                'status' => OrderStatus::PAID,
                'subtotal' => 320000,
                'shipping_cost' => 0,
                'other_cost' => 0,
                'total' => 320000,
                'payment_status' => 'paid',
                'invoiced_at' => now(),
                'paid_at' => now(),
                'created_at' => now(),
                'cogs_total' => 265000,
                'note' => 'Bayar tunai di kasir gudang',
            ]
        );
    }
}
