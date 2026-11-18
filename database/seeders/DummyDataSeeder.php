<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\CustomerPrice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ReservationService;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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

        // 3. Kategori
        $catMinyak = Category::firstOrCreate(['slug' => 'minyak-goreng'], ['name' => 'Minyak Goreng']);
        $catBeras = Category::firstOrCreate(['slug' => 'beras-tepung'], ['name' => 'Beras & Tepung']);
        $catGula = Category::firstOrCreate(['slug' => 'gula-kopi-teh'], ['name' => 'Gula & Kopi']);
        $catMi = Category::firstOrCreate(['slug' => 'mi-instan'], ['name' => 'Mi Instan']);

        // 4. Master Produk & Units & Pricing
        // Produk 1: Minyak Goreng Sania 2L
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

        // Produk 2: Beras Pandan Wangi 5kg
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

        // Produk 3: Gulaku Kuning 1kg
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

        // Produk 4: Indomie Goreng Original 85g
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

        // Produk 5: Kopi Kapal Api Special Mix 24g
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

        // Produk 6: Tepung Terigu Segitiga Biru 1kg
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

        // 5. Inbound Stok (Barang Masuk) melalui StockService
        $stockService = app(StockService::class);
        $reservationService = app(ReservationService::class);

        // Hanya inbound jika belum ada batch
        if (DB::table('stock_batches')->count() === 0) {
            // Inbound 1: Dari PT Wilmar (Minyak Sania 120 Pouch = 20 Dus)
            $stockService->receive([
                'supplier_id' => $supWilmar->id,
                'warehouse_id' => $warehouse->id,
                'received_date' => now()->subDays(5)->toDateString(),
                'shipping_cost' => 50000,
                'other_cost' => 0,
                'items' => [
                    ['product_id' => $prodSania->id, 'qty_base' => 120, 'unit_price' => 30500],
                ],
            ], $staf->id);

            // Inbound 2: Dari Indofood (Indomie 400 Bks = 10 Dus, Terigu 120 Pcs = 10 Dus)
            $stockService->receive([
                'supplier_id' => $supIndofood->id,
                'warehouse_id' => $warehouse->id,
                'received_date' => now()->subDays(3)->toDateString(),
                'shipping_cost' => 80000,
                'other_cost' => 20000,
                'items' => [
                    ['product_id' => $prodIndomie->id, 'qty_base' => 400, 'unit_price' => 2650],
                    ['product_id' => $prodTerigu->id, 'qty_base' => 120, 'unit_price' => 11000],
                ],
            ], $staf->id);

            // Inbound 3: Dari Distributor Sembako (Beras 50 Karung, Gulaku 200 Bks, Kopi 600 Sachet)
            $stockService->receive([
                'supplier_id' => $supFood->id,
                'warehouse_id' => $warehouse->id,
                'received_date' => now()->subDays(2)->toDateString(),
                'shipping_cost' => 120000,
                'other_cost' => 0,
                'items' => [
                    ['product_id' => $prodBeras->id, 'qty_base' => 50, 'unit_price' => 64000],
                    ['product_id' => $prodGula->id, 'qty_base' => 200, 'unit_price' => 14800],
                    ['product_id' => $prodKopi->id, 'qty_base' => 600, 'unit_price' => 1150],
                ],
            ], $staf->id);
        }

        // 6. Dummy Orders untuk Percobaan Admin & Customer
        if (Order::count() === 0) {
            // ORDER A: Menunggu Ongkir dari Budi Santoso (Hold Stok Aktif!)
            // Isi: 2 Dus Sania (12 Pouch) + 1 Dus Indomie (40 Bks)
            $orderA = Order::create([
                'user_id' => $customerBudi->id,
                'warehouse_id' => $warehouse->id,
                'channel' => 'catalog',
                'customer_name' => $customerBudi->name,
                'status' => OrderStatus::MENUNGGU_ONGKIR,
                'subtotal' => (2 * 205000) + 116000, // 410.000 + 116.000 = 526.000
                'shipping_cost' => 0,
                'other_cost' => 0,
                'total' => 526000,
                'payment_status' => 'unpaid',
                'shipping_due_at' => now()->addHours(3),
                'note' => 'Tolong dicek ongkir via kurir motor/mobil pickup ya min.',
            ]);

            OrderItem::create([
                'order_id' => $orderA->id,
                'product_id' => $prodSania->id,
                'unit_id' => $unitSaniaDus->id,
                'qty' => 2,
                'qty_base' => 12,
                'unit_price' => 205000,
                'price_source' => 'product_prices',
                'line_total' => 410000,
            ]);

            OrderItem::create([
                'order_id' => $orderA->id,
                'product_id' => $prodIndomie->id,
                'unit_id' => $unitMiDus->id,
                'qty' => 1,
                'qty_base' => 40,
                'unit_price' => 116000,
                'price_source' => 'product_prices',
                'line_total' => 116000,
            ]);

            // Kunci stok Order A dengan ReservationService
            $reservationService->reserve(
                orderId: $orderA->id,
                warehouseId: $warehouse->id,
                items: [
                    ['product_id' => $prodSania->id, 'qty_base' => 12],
                    ['product_id' => $prodIndomie->id, 'qty_base' => 40],
                ],
                expiresAt: now()->addHours(3)
            );

            // ORDER B: Menunggu Pembayaran (Pending Payment) dari Toko Berkah Jaya
            // Ongkir sudah diinput admin, nomor invoice sudah terbit!
            $orderB = Order::create([
                'user_id' => $customerToko->id,
                'warehouse_id' => $warehouse->id,
                'channel' => 'catalog',
                'customer_name' => $customerToko->name,
                'invoice_no' => 'INV/'.now()->format('Y/m').'/0001',
                'status' => OrderStatus::PENDING_PAYMENT,
                'subtotal' => (2 * 330000) + (2 * 111000), // 660.000 + 222.000 = 882.000 (harga khusus)
                'shipping_cost' => 35000,
                'other_cost' => 0,
                'total' => 882000 + 35000, // 917.000
                'payment_status' => 'unpaid',
                'invoiced_at' => now(),
                'expires_at' => now()->addHours(11),
                'note' => 'Dikirim pakai kurir Lalamove toko',
            ]);

            OrderItem::create([
                'order_id' => $orderB->id,
                'product_id' => $prodGula->id,
                'unit_id' => $unitGulaDus->id,
                'qty' => 2,
                'qty_base' => 40,
                'unit_price' => 330000,
                'price_source' => 'product_prices',
                'line_total' => 660000,
            ]);

            OrderItem::create([
                'order_id' => $orderB->id,
                'product_id' => $prodIndomie->id,
                'unit_id' => $unitMiDus->id,
                'qty' => 2,
                'qty_base' => 80,
                'unit_price' => 111000,
                'price_source' => 'customer_prices',
                'line_total' => 222000,
            ]);

            $reservationService->reserve(
                orderId: $orderB->id,
                warehouseId: $warehouse->id,
                items: [
                    ['product_id' => $prodGula->id, 'qty_base' => 40],
                    ['product_id' => $prodIndomie->id, 'qty_base' => 80],
                ],
                expiresAt: now()->addHours(11)
            );

            // ORDER C: Order Selesai & Lunas (Stok sudah terpotong via FIFO!)
            $orderC = Order::create([
                'user_id' => $customerBudi->id,
                'warehouse_id' => $warehouse->id,
                'channel' => 'manual',
                'customer_name' => $customerBudi->name,
                'invoice_no' => 'INV/'.now()->format('Y/m').'/0002',
                'status' => OrderStatus::PAID,
                'subtotal' => 75000 + (5 * 17500), // 75.000 + 87.500 = 162.500
                'shipping_cost' => 15000,
                'other_cost' => 0,
                'total' => 177500,
                'payment_status' => 'paid',
                'invoiced_at' => now()->subDay(),
                'paid_at' => now()->subHours(20),
                'note' => 'Pesanan diambil langsung di gudang',
            ]);

            $itemC1 = OrderItem::create([
                'order_id' => $orderC->id,
                'product_id' => $prodBeras->id,
                'unit_id' => $unitBerasKarung->id,
                'qty' => 1,
                'qty_base' => 1,
                'unit_price' => 75000,
                'price_source' => 'product_prices',
                'line_total' => 75000,
            ]);

            $itemC2 = OrderItem::create([
                'order_id' => $orderC->id,
                'product_id' => $prodGula->id,
                'unit_id' => $unitGulaBks->id,
                'qty' => 5,
                'qty_base' => 5,
                'unit_price' => 17500,
                'price_source' => 'product_prices',
                'line_total' => 87500,
            ]);

            // Potong stok FIFO langsung untuk Order C
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
    }
}
