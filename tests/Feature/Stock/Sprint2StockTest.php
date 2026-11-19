<?php

use App\Exceptions\InconsistentStockException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidAdjustmentException;
use App\Models\InboundItem;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function wh(): Warehouse
{
    return Warehouse::first() ?? Warehouse::create(['name' => 'Gudang Utama', 'is_default' => true]);
}

function prod(string $sku = 'SKU-1'): Product
{
    return Product::create(['sku' => $sku, 'name' => "Produk $sku", 'base_unit' => 'pcs', 'min_stock' => 5]);
}

function level(int $productId): object
{
    return DB::table('stock_levels')->where('product_id', $productId)->where('warehouse_id', wh()->id)->first();
}

function receive(Product $p, int $qty, string $price, array $extra = []): void
{
    app(StockService::class)->receive(array_merge([
        'warehouse_id' => wh()->id,
        'received_date' => '2026-11-17',
        'items' => [['product_id' => $p->id, 'qty_base' => $qty, 'unit_price' => $price]],
    ], $extra));
}

it('menghitung landed cost proporsional terhadap nilai barang', function () {
    $a = prod('A');
    $b = prod('B');

    app(StockService::class)->receive([
        'warehouse_id' => wh()->id,
        'received_date' => '2026-11-17',
        'shipping_cost' => 100000,
        'items' => [
            ['product_id' => $a->id, 'qty_base' => 100, 'unit_price' => '10000'],
            ['product_id' => $b->id, 'qty_base' => 50,  'unit_price' => '20000'],
        ],
    ]);

    expect((float) StockBatch::where('product_id', $a->id)->value('unit_cost_landed'))->toBe(10500.0);
    expect((float) StockBatch::where('product_id', $b->id)->value('unit_cost_landed'))->toBe(21000.0);
});

it('total alokasi biaya selalu sama persis dengan biaya tambahan', function () {
    $items = collect(['X', 'Y', 'Z'])->map(fn ($s) => [
        'product_id' => prod($s)->id, 'qty_base' => 10, 'unit_price' => '100000',
    ])->all();

    app(StockService::class)->receive([
        'warehouse_id' => wh()->id,
        'received_date' => '2026-11-17',
        'shipping_cost' => 100000,
        'items' => $items,
    ]);

    expect(round((float) InboundItem::sum('allocated_cost'), 2))->toBe(100000.0);
});

it('barang masuk membuat batch, menaikkan on_hand, dan mencatat movement IN', function () {
    $p = prod();
    receive($p, 10, '5000');

    expect(StockBatch::where('product_id', $p->id)->count())->toBe(1);
    expect((int) level($p->id)->on_hand)->toBe(10);
    expect(StockMovement::where('product_id', $p->id)->where('qty_base', 10)->count())->toBe(1);
});

it('barang keluar memotong sebagian dari satu batch dan menghitung HPP', function () {
    $p = prod();
    receive($p, 100, '10000');

    $result = app(StockService::class)->issue($p->id, wh()->id, 30);

    expect((float) $result['cogs'])->toBe(300000.0);
    expect((int) StockBatch::where('product_id', $p->id)->value('qty_remaining'))->toBe(70);
    expect((int) level($p->id)->on_hand)->toBe(70);
});

it('FIFO memotong batch tertua dulu, HPP dari gabungan dua batch', function () {
    $p = prod();
    receive($p, 40, '10500');
    receive($p, 100, '11000');

    $result = app(StockService::class)->issue($p->id, wh()->id, 70);

    // 40 x 10.500 + 30 x 11.000 = 750.000
    expect((float) $result['cogs'])->toBe(750000.0);
    expect(count($result['batches']))->toBe(2);

    $remaining = StockBatch::where('product_id', $p->id)->orderBy('id')->pluck('qty_remaining')->map(fn ($v) => (int) $v)->all();
    expect($remaining)->toBe([0, 70]);
});

it('menolak barang keluar bila stok kurang dan tidak mengubah data', function () {
    $p = prod();
    receive($p, 5, '1000');

    expect(fn () => app(StockService::class)->issue($p->id, wh()->id, 6))
        ->toThrow(InsufficientStockException::class);

    expect((int) level($p->id)->on_hand)->toBe(5);
    expect((int) StockBatch::where('product_id', $p->id)->value('qty_remaining'))->toBe(5);
    expect(StockMovement::where('qty_base', '<', 0)->count())->toBe(0);
});

it('stok yang di-hold tidak bisa dijual manual', function () {
    $p = prod();
    receive($p, 10, '1000');
    DB::table('stock_levels')->where('product_id', $p->id)->update(['reserved' => 8]);

    expect(fn () => app(StockService::class)->issue($p->id, wh()->id, 5))
        ->toThrow(InsufficientStockException::class);

    app(StockService::class)->issue($p->id, wh()->id, 2); // sisa available = 2
    expect((int) level($p->id)->on_hand)->toBe(8);
});

it('barang keluar dari reservasi menurunkan on_hand dan reserved bersama', function () {
    $p = prod();
    receive($p, 10, '1000');
    DB::table('stock_levels')->where('product_id', $p->id)->update(['reserved' => 8]);

    app(StockService::class)->issue($p->id, wh()->id, 8, fromReservation: true);

    $l = level($p->id);
    expect([(int) $l->on_hand, (int) $l->reserved])->toBe([2, 0]);
});

it('total movement sama dengan on_hand', function () {
    $p = prod();
    receive($p, 10, '1000');
    receive($p, 5, '1200');
    app(StockService::class)->issue($p->id, wh()->id, 7);

    expect((int) StockMovement::where('product_id', $p->id)->sum('qty_base'))->toBe((int) level($p->id)->on_hand);
});

it('opname mengurangi stok dengan FIFO dan mencatat ADJUST negatif', function () {
    $p = prod();
    receive($p, 10, '1000');

    $diff = app(StockService::class)->adjust($p->id, wh()->id, 7, 'Barang rusak');

    expect($diff)->toBe(-3);
    expect((int) level($p->id)->on_hand)->toBe(7);
    expect((int) StockBatch::where('product_id', $p->id)->value('qty_remaining'))->toBe(7);
});

it('opname menambah stok dengan membuat batch baru', function () {
    $p = prod();
    receive($p, 10, '1000');

    app(StockService::class)->adjust($p->id, wh()->id, 12, 'Selisih lebih', unitCost: '1000.0000');

    expect((int) level($p->id)->on_hand)->toBe(12);
    expect(StockBatch::where('product_id', $p->id)->whereNull('inbound_item_id')->count())->toBe(1);
});

it('opname ditolak bila di bawah stok yang di-hold atau tanpa alasan', function () {
    $p = prod();
    receive($p, 10, '1000');
    DB::table('stock_levels')->where('product_id', $p->id)->update(['reserved' => 6]);

    expect(fn () => app(StockService::class)->adjust($p->id, wh()->id, 4, 'Salah hitung'))
        ->toThrow(InvalidAdjustmentException::class);
    expect(fn () => app(StockService::class)->adjust($p->id, wh()->id, 9, '  '))
        ->toThrow(InvalidAdjustmentException::class);
});

it('melempar InconsistentStock bila batch tidak cocok dengan stock_levels, dan transaksi dibatalkan', function () {
    $p = prod();
    receive($p, 10, '1000');
    StockBatch::where('product_id', $p->id)->update(['qty_remaining' => 3]); // sengaja merusak data

    expect(fn () => app(StockService::class)->issue($p->id, wh()->id, 8))
        ->toThrow(InconsistentStockException::class);

    expect((int) level($p->id)->on_hand)->toBe(10);
    expect((int) StockBatch::where('product_id', $p->id)->value('qty_remaining'))->toBe(3);
});
