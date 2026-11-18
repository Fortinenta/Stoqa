<?php

use App\Enums\OrderStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockLevel;
use App\Models\StockReservation;
use App\Models\Warehouse;
use App\Services\ReservationService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->stockService = app(StockService::class);
    $this->reservationService = app(ReservationService::class);

    $this->category = Category::create(['name' => 'Minuman']);
    $this->warehouse = Warehouse::create(['name' => 'Gudang Utama', 'is_default' => true]);

    $this->product = Product::create([
        'sku' => 'MNM-001',
        'name' => 'Kopi Botol 250ml',
        'category_id' => $this->category->id,
        'base_unit' => 'pcs',
        'min_stock' => 5,
        'is_active' => true,
    ]);

    $this->unitPcs = ProductUnit::create([
        'product_id' => $this->product->id,
        'unit_name' => 'pcs',
        'conversion_to_base' => 1,
    ]);

    // Stok awal 50 pcs @ Rp5.000
    $this->stockService->receiveInbound(
        ['warehouse_id' => $this->warehouse->id, 'shipping_cost' => 0, 'other_cost' => 0],
        [['product_id' => $this->product->id, 'qty_base' => 50, 'unit_price' => 5000]]
    );
});

test('reserve menahan stok dan menaikkan reserved serta menjaga stok tersedia akurat', function () {
    $order = Order::create([
        'warehouse_id' => $this->warehouse->id,
        'status' => OrderStatus::MENUNGGU_ONGKIR,
        'shipping_due_at' => now()->addHours(3),
    ]);

    $reservations = $this->reservationService->reserve(
        orderId: $order->id,
        warehouseId: $this->warehouse->id,
        items: [['product_id' => $this->product->id, 'qty_base' => 20]],
        expiresAt: now()->addHours(3)
    );

    expect($reservations)->toHaveCount(1);
    expect($reservations->first()->status)->toBe(ReservationStatus::ACTIVE);

    $level = StockLevel::where('product_id', $this->product->id)->first();
    expect($level->on_hand)->toBe(50)
        ->and($level->reserved)->toBe(20)
        ->and($level->available)->toBe(30);
});

test('reserve melempar InsufficientStockException bila permintaan melebihi stok tersedia', function () {
    $order1 = Order::create([
        'warehouse_id' => $this->warehouse->id,
        'status' => OrderStatus::MENUNGGU_ONGKIR,
    ]);

    // Checkout 1 hold 40 pcs -> sisa available = 10 pcs
    $this->reservationService->reserve(
        orderId: $order1->id,
        warehouseId: $this->warehouse->id,
        items: [['product_id' => $this->product->id, 'qty_base' => 40]],
        expiresAt: now()->addHours(3)
    );

    $order2 = Order::create([
        'warehouse_id' => $this->warehouse->id,
        'status' => OrderStatus::MENUNGGU_ONGKIR,
    ]);

    // Checkout 2 mencoba minta 20 pcs (stok tersedia hanya 10) -> Harus DITOLAK
    $this->reservationService->reserve(
        orderId: $order2->id,
        warehouseId: $this->warehouse->id,
        items: [['product_id' => $this->product->id, 'qty_base' => 20]],
        expiresAt: now()->addHours(3)
    );
})->throws(InsufficientStockException::class);

test('release melepaskan hold stok dan mengembalikan jumlah tersedia', function () {
    $order = Order::create([
        'warehouse_id' => $this->warehouse->id,
        'status' => OrderStatus::MENUNGGU_ONGKIR,
    ]);

    $this->reservationService->reserve(
        orderId: $order->id,
        warehouseId: $this->warehouse->id,
        items: [['product_id' => $this->product->id, 'qty_base' => 15]],
        expiresAt: now()->addHours(3)
    );

    // Lepas reservasi (misal order ditolak/dibatalkan)
    $this->reservationService->release($order->id, ReservationStatus::RELEASED);

    $level = StockLevel::where('product_id', $this->product->id)->first();
    expect($level->reserved)->toBe(0)
        ->and($level->available)->toBe(50);

    $reservation = StockReservation::where('order_id', $order->id)->first();
    expect($reservation->status)->toBe(ReservationStatus::RELEASED);
});

test('consume mengubah status reservasi menjadi CONSUMED dan memotong fisik FIFO', function () {
    $order = Order::create([
        'warehouse_id' => $this->warehouse->id,
        'status' => OrderStatus::PENDING_PAYMENT,
    ]);

    $orderItem = OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'unit_id' => $this->unitPcs->id,
        'qty' => 25,
        'qty_base' => 25,
        'unit_price' => 8000,
        'line_total' => 200000,
    ]);

    $this->reservationService->reserve(
        orderId: $order->id,
        warehouseId: $this->warehouse->id,
        items: [['product_id' => $this->product->id, 'qty_base' => 25]],
        expiresAt: now()->addHours(12)
    );

    // Pembayaran lunas -> consume
    $fulfillment = $this->reservationService->consume($order);

    expect((float) $fulfillment['total_cogs'])->toEqual(125000.0); // 25 pcs @ Rp5.000

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::PAID)
        ->and((float) $order->cogs_total)->toEqual(125000.0);

    $level = StockLevel::where('product_id', $this->product->id)->first();
    expect($level->on_hand)->toBe(25)
        ->and($level->reserved)->toBe(0)
        ->and($level->available)->toBe(25);
});

test('expireOverdue melepas seluruh reservasi yang melewati batas waktu secara otomatis', function () {
    $order = Order::create([
        'warehouse_id' => $this->warehouse->id,
        'status' => OrderStatus::MENUNGGU_ONGKIR,
    ]);

    // Reservasi dengan batas kadaluwarsa di masa lalu
    $this->reservationService->reserve(
        orderId: $order->id,
        warehouseId: $this->warehouse->id,
        items: [['product_id' => $this->product->id, 'qty_base' => 30]],
        expiresAt: now()->subMinutes(10)
    );

    $expiredCount = $this->reservationService->expireOverdue();

    expect($expiredCount)->toBe(1);

    $level = StockLevel::where('product_id', $this->product->id)->first();
    expect($level->reserved)->toBe(0)
        ->and($level->available)->toBe(50);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::EXPIRED);
});
