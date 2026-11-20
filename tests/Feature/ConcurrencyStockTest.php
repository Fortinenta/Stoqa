<?php

use App\Models\Category;
use App\Models\InboundItem;
use App\Models\InboundOrder;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

test('uji konkurensi: dua proses menjual 10 dari stok 10 bersamaan, tepat satu berhasil dan stok tidak minus', function () {
    $dbHost = env('DB_TEST_HOST') ?: (env('DB_HOST') ?: '127.0.0.1');
    $dbPort = env('DB_TEST_PORT') ?: (env('DB_PORT') ?: '5432');
    $dbName = env('DB_TEST_DATABASE') ?: (config('database.connections.pgsql.database') && config('database.connections.pgsql.database') !== ':memory:' ? config('database.connections.pgsql.database') : 'stok_gudang');
    $dbUser = env('DB_TEST_USERNAME') ?: (config('database.connections.pgsql.username') && config('database.connections.pgsql.username') !== ':memory:' ? config('database.connections.pgsql.username') : 'postgres');
    $dbPass = env('DB_TEST_PASSWORD') ?? env('DB_PASSWORD') ?? config('database.connections.pgsql.password');

    config([
        'database.default' => 'pgsql',
        'database.connections.pgsql.host' => $dbHost,
        'database.connections.pgsql.port' => $dbPort,
        'database.connections.pgsql.database' => $dbName,
        'database.connections.pgsql.username' => $dbUser,
        'database.connections.pgsql.password' => $dbPass,
    ]);
    DB::purge('pgsql');

    try {
        DB::connection('pgsql')->getPdo();
    } catch (Throwable $e) {
        $this->markTestSkipped('Koneksi PostgreSQL untuk uji konkurensi tidak tersedia: '.$e->getMessage());
    }

    $stockService = app(StockService::class);

    $category = Category::firstOrCreate(['name' => 'Kategori Uji Race'], ['slug' => 'kategori-uji-race']);
    $warehouse = Warehouse::firstOrCreate(['id' => 1], ['name' => 'Gudang Utama', 'is_default' => true]);

    $product = Product::create([
        'sku' => 'TEST-RACE-'.uniqid(),
        'name' => 'Produk Race Condition Test',
        'category_id' => $category->id,
        'base_unit' => 'pcs',
        'min_stock' => 5,
        'is_active' => true,
    ]);

    // Masukkan stok awal persis 10 unit ke PostgreSQL
    $inbound = $stockService->receive([
        'warehouse_id' => $warehouse->id,
        'received_date' => now()->toDateString(),
        'shipping_cost' => 0,
        'other_cost' => 0,
        'items' => [
            ['product_id' => $product->id, 'qty_base' => 10, 'unit_price' => 10000],
        ],
    ]);

    expect($stockService->available($product->id, $warehouse->id))->toBe(10);

    // Jalankan 2 proses command artisan bersamaan lewat Process::pool dengan koneksi pgsql
    $basePath = base_path();
    $env = [
        'DB_CONNECTION' => 'pgsql',
        'DB_HOST' => $dbHost,
        'DB_PORT' => $dbPort,
        'DB_DATABASE' => $dbName,
        'DB_USERNAME' => $dbUser,
        'DB_PASSWORD' => $dbPass,
    ];

    $pool = Process::pool(function ($pool) use ($product, $warehouse, $basePath, $env) {
        $cmd = "php artisan stock:issue {$product->id} {$warehouse->id} 10";
        $pool->path($basePath)->env($env)->command($cmd);
        $pool->path($basePath)->env($env)->command($cmd);
    });

    $results = $pool->start()->wait();

    $exitCodes = [
        $results[0]->exitCode(),
        $results[1]->exitCode(),
    ];

    sort($exitCodes);

    // Tepat satu harus sukses (exit code 0), dan satu lagi harus ditolak (exit code 2)
    expect($exitCodes)->toEqual([0, 2]);

    // Stok akhir di PostgreSQL harus tepat 0 (tidak pernah minus)
    $finalOnHand = (int) DB::table('stock_levels')
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->value('on_hand');

    expect($finalOnHand)->toBe(0);

    // Bersihkan data uji di PostgreSQL
    StockMovement::where('product_id', $product->id)->delete();
    DB::table('stock_batches')->where('product_id', $product->id)->delete();
    DB::table('stock_levels')->where('product_id', $product->id)->delete();
    InboundItem::where('product_id', $product->id)->delete();
    InboundOrder::where('id', $inbound->id)->delete();
    $product->forceDelete();
});
