<?php

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\InboundOrder;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->stockService = app(StockService::class);

    $this->category = Category::create(['name' => 'Snack']);
    $this->warehouse = Warehouse::create(['name' => 'Gudang Utama', 'is_default' => true]);

    $this->productA = Product::create([
        'sku' => 'PRD-A',
        'name' => 'Produk A',
        'category_id' => $this->category->id,
        'base_unit' => 'pcs',
        'min_stock' => 10,
        'is_active' => true,
    ]);

    $this->productB = Product::create([
        'sku' => 'PRD-B',
        'name' => 'Produk B',
        'category_id' => $this->category->id,
        'base_unit' => 'pcs',
        'min_stock' => 10,
        'is_active' => true,
    ]);
});

test('landed cost: dua item dengan biaya Rp100.000 teralokasi proporsional tanpa selisih', function () {
    // Skenario Panduan 8.1:
    // Item A: 100 pcs @ Rp10.000 = Rp1.000.000 (50% dari total nilai Rp2.000.000)
    // Item B: 50 pcs @ Rp20.000  = Rp1.000.000 (50% dari total nilai Rp2.000.000)
    // Ongkir Rp100.000
    // Harapan:
    // Alokasi biaya A = Rp50.000 -> Modal A = (1.000.000 + 50.000) / 100 = Rp10.500
    // Alokasi biaya B = Rp50.000 -> Modal B = (1.000.000 + 50.000) / 50 = Rp21.000
    // Total alokasi = tepat Rp100.000
    $order = $this->stockService->receive([
        'warehouse_id' => $this->warehouse->id,
        'received_date' => now()->toDateString(),
        'shipping_cost' => 100000,
        'other_cost' => 0,
        'items' => [
            ['product_id' => $this->productA->id, 'qty_base' => 100, 'unit_price' => 10000],
            ['product_id' => $this->productB->id, 'qty_base' => 50,  'unit_price' => 20000],
        ],
    ]);

    expect($order)->toBeInstanceOf(InboundOrder::class);

    $batchA = StockBatch::where('product_id', $this->productA->id)->first();
    $batchB = StockBatch::where('product_id', $this->productB->id)->first();

    expect((float) $batchA->unit_cost_landed)->toEqual(10500.0)
        ->and((float) $batchB->unit_cost_landed)->toEqual(21000.0);

    $totalAllocated = $order->items->sum('allocated_cost');
    expect((float) $totalAllocated)->toEqual(100000.0);
});

test('pembulatan: tiga item dengan biaya yang tidak habis dibagi teralokasi tepat tanpa selisih sen', function () {
    // 3 item dengan nilai masing-masing Rp10.000, total Rp30.000
    // Biaya tambahan Rp10.000 (10.000 / 3 = 3333.333...)
    // Item 1: 3333.33
    // Item 2: 3333.33
    // Item 3 (terakhir): 3333.34
    // Total harus tepat Rp10.000,00
    $order = $this->stockService->receive([
        'warehouse_id' => $this->warehouse->id,
        'received_date' => now()->toDateString(),
        'shipping_cost' => 10000,
        'other_cost' => 0,
        'items' => [
            ['product_id' => $this->productA->id, 'qty_base' => 1, 'unit_price' => 10000],
            ['product_id' => $this->productB->id, 'qty_base' => 1, 'unit_price' => 10000],
            ['product_id' => $this->productA->id, 'qty_base' => 1, 'unit_price' => 10000],
        ],
    ]);

    $totalAllocated = $order->items->sum('allocated_cost');
    expect((float) $totalAllocated)->toEqual(10000.0);
});

test('receive: barang masuk satu produk membuat batch, menaikkan on_hand, dan mencatat movement IN', function () {
    $this->stockService->receive([
        'warehouse_id' => $this->warehouse->id,
        'received_date' => now()->toDateString(),
        'shipping_cost' => 0,
        'other_cost' => 0,
        'items' => [
            ['product_id' => $this->productA->id, 'qty_base' => 80, 'unit_price' => 5000],
        ],
    ]);

    expect(StockBatch::where('product_id', $this->productA->id)->count())->toBe(1);
    expect($this->stockService->available($this->productA->id, $this->warehouse->id))->toBe(80);

    $movement = StockMovement::where('product_id', $this->productA->id)->first();
    expect($movement->type)->toBe(StockMovementType::IN)
        ->and($movement->qty_base)->toBe(80);
});

test('FIFO satu batch: penjualan sebagian memotong qty_remaining dan menghitung HPP sesuai modal', function () {
    $this->stockService->receive([
        'warehouse_id' => $this->warehouse->id,
        'received_date' => now()->toDateString(),
        'shipping_cost' => 0,
        'other_cost' => 0,
        'items' => [
            ['product_id' => $this->productA->id, 'qty_base' => 100, 'unit_price' => 15000],
        ],
    ]);

    $result = $this->stockService->issue($this->productA->id, $this->warehouse->id, 40);

    expect((float) $result['cogs'])->toEqual(600000.0); // 40 x 15.000
    expect($this->stockService->available($this->productA->id, $this->warehouse->id))->toBe(60);

    $batch = StockBatch::where('product_id', $this->productA->id)->first();
    expect($batch->qty_remaining)->toBe(60);
});

test('FIFO dua batch: skenario panduan 8.2 (B1 40 @10.500, B2 100 @11.000, jual 70 = HPP 750.000)', function () {
    // Batch 1: 40 pcs @ Rp10.500
    $this->stockService->receive([
        'warehouse_id' => $this->warehouse->id,
        'received_date' => now()->subDay()->toDateString(),
        'shipping_cost' => 0,
        'other_cost' => 0,
        'items' => [
            ['product_id' => $this->productA->id, 'qty_base' => 40, 'unit_price' => 10500],
        ],
    ]);

    // Batch 2: 100 pcs @ Rp11.000
    $this->stockService->receive([
        'warehouse_id' => $this->warehouse->id,
        'received_date' => now()->toDateString(),
        'shipping_cost' => 0,
        'other_cost' => 0,
        'items' => [
            ['product_id' => $this->productA->id, 'qty_base' => 100, 'unit_price' => 11000],
        ],
    ]);

    // Jual 70 unit mengambil 40 dari B1 + 30 dari B2
    // HPP = (40 x 10.500) + (30 x 11.000) = 420.000 + 330.000 = Rp750.000
    $result = $this->stockService->issue($this->productA->id, $this->warehouse->id, 70);

    expect((float) $result['cogs'])->toEqual(750000.0)
        ->and($result['batches'])->toHaveCount(2);

    // B1 habis (0), B2 tersisa 70
    $batches = StockBatch::where('product_id', $this->productA->id)->orderBy('id', 'asc')->get();
    expect($batches[0]->qty_remaining)->toBe(0)
        ->and($batches[1]->qty_remaining)->toBe(70);

    expect($this->stockService->available($this->productA->id, $this->warehouse->id))->toBe(70);
});

test('stok kurang: melempar InsufficientStockException dan tidak ada data yang berubah', function () {
    $this->stockService->receive([
        'warehouse_id' => $this->warehouse->id,
        'received_date' => now()->toDateString(),
        'shipping_cost' => 0,
        'other_cost' => 0,
        'items' => [
            ['product_id' => $this->productA->id, 'qty_base' => 10, 'unit_price' => 5000],
        ],
    ]);

    try {
        $this->stockService->issue($this->productA->id, $this->warehouse->id, 15);
        $this->fail('Harusnya melempar InsufficientStockException');
    } catch (InsufficientStockException $e) {
        expect($this->stockService->available($this->productA->id, $this->warehouse->id))->toBe(10);
        expect(StockMovement::where('type', StockMovementType::OUT)->count())->toBe(0);
    }
});

test('stok ter-hold: on_hand 10, reserved 8, jual 5 manual ditolak karena available hanya 2', function () {
    $this->stockService->receive([
        'warehouse_id' => $this->warehouse->id,
        'received_date' => now()->toDateString(),
        'shipping_cost' => 0,
        'other_cost' => 0,
        'items' => [
            ['product_id' => $this->productA->id, 'qty_base' => 10, 'unit_price' => 5000],
        ],
    ]);

    // Simulasi hold 8 unit
    DB::table('stock_levels')
        ->where('product_id', $this->productA->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->update(['reserved' => 8]);

    expect($this->stockService->available($this->productA->id, $this->warehouse->id))->toBe(2);

    // Penjualan manual 5 harus ditolak
    $this->expectException(InsufficientStockException::class);
    $this->stockService->issue($this->productA->id, $this->warehouse->id, 5, false);
});

test('dari reservasi: keluar 8 dengan fromReservation = true menurunkan on_hand dan reserved bersama', function () {
    $this->stockService->receive([
        'warehouse_id' => $this->warehouse->id,
        'received_date' => now()->toDateString(),
        'shipping_cost' => 0,
        'other_cost' => 0,
        'items' => [
            ['product_id' => $this->productA->id, 'qty_base' => 10, 'unit_price' => 5000],
        ],
    ]);

    DB::table('stock_levels')
        ->where('product_id', $this->productA->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->update(['reserved' => 8]);

    // Pengeluaran dari reservasi sebesar 8
    $result = $this->stockService->issue($this->productA->id, $this->warehouse->id, 8, true);

    expect((float) $result['cogs'])->toEqual(40000.0);

    $level = DB::table('stock_levels')
        ->where('product_id', $this->productA->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();

    // on_hand 10 - 8 = 2, reserved 8 - 8 = 0
    expect($level->on_hand)->toBe(2)
        ->and($level->reserved)->toBe(0)
        ->and($level->reserved)->toBeLessThanOrEqual($level->on_hand);
});

test('atomik: kegagalan di tengah transaksi membatalkan seluruh operasi tanpa ada data parsial', function () {
    try {
        DB::transaction(function () {
            $this->stockService->receive([
                'warehouse_id' => $this->warehouse->id,
                'received_date' => now()->toDateString(),
                'shipping_cost' => 0,
                'other_cost' => 0,
                'items' => [
                    ['product_id' => $this->productA->id, 'qty_base' => 50, 'unit_price' => 5000],
                ],
            ]);

            throw new RuntimeException('Simulasi gagal di tengah jalan!');
        });
    } catch (RuntimeException $e) {
        // expected
    }

    expect(StockBatch::where('product_id', $this->productA->id)->count())->toBe(0);
    expect(StockMovement::where('product_id', $this->productA->id)->count())->toBe(0);
    expect($this->stockService->available($this->productA->id, $this->warehouse->id))->toBe(0);
});

test('movement append-only: total mutasi IN dan OUT sama persis dengan stok on_hand fisik', function () {
    // Masuk 100
    $this->stockService->receive([
        'warehouse_id' => $this->warehouse->id,
        'received_date' => now()->toDateString(),
        'shipping_cost' => 0,
        'other_cost' => 0,
        'items' => [
            ['product_id' => $this->productA->id, 'qty_base' => 100, 'unit_price' => 5000],
        ],
    ]);

    // Keluar 30
    $this->stockService->issue($this->productA->id, $this->warehouse->id, 30);

    // Keluar 20
    $this->stockService->issue($this->productA->id, $this->warehouse->id, 20);

    // Masuk 50 lagi
    $this->stockService->receive([
        'warehouse_id' => $this->warehouse->id,
        'received_date' => now()->toDateString(),
        'shipping_cost' => 0,
        'other_cost' => 0,
        'items' => [
            ['product_id' => $this->productA->id, 'qty_base' => 50, 'unit_price' => 6000],
        ],
    ]);

    $totalMovements = (int) StockMovement::where('product_id', $this->productA->id)->sum('qty_base');
    $onHand = (int) DB::table('stock_levels')
        ->where('product_id', $this->productA->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->value('on_hand');

    expect($totalMovements)->toBe(100) // 100 - 30 - 20 + 50 = 100
        ->and($totalMovements)->toBe($onHand);
});
