<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderCost;
use App\Models\Setting;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'staf_gudang', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

test('perhitungan laba bersih memperhitungkan total tagihan, hpp cogs, dan biaya riil pengeluaran operasional', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $warehouse = Warehouse::create(['name' => 'Gudang Utama', 'is_default' => true]);

    // Order: Subtotal 200.000, Ongkir Pelanggan 20.000, Total 220.000, HPP Modal 150.000
    // Laba Kotor: 200.000 - 150.000 = 50.000
    $order = Order::create([
        'user_id' => $admin->id,
        'warehouse_id' => $warehouse->id,
        'channel' => 'catalog',
        'customer_name' => 'Pelanggan Uji Laba',
        'status' => OrderStatus::PAID,
        'subtotal' => 200000,
        'shipping_cost' => 20000,
        'total' => 220000,
        'cogs_total' => 150000,
    ]);

    // Biaya riil: Ongkir keluar riil kurir Rp 18.000, Packing Rp 5.000
    OrderCost::create([
        'order_id' => $order->id,
        'type' => 'shipping_out',
        'amount' => 18000,
        'note' => 'Resi JNE REG 12345678',
    ]);

    OrderCost::create([
        'order_id' => $order->id,
        'type' => 'packing',
        'amount' => 5000,
        'note' => 'Kardus tebal & bubble wrap',
    ]);

    $order->load('costs');

    // Total Biaya Riil: 18.000 + 5.000 = 23.000
    // Laba Bersih = Total Tagihan (220.000) - HPP (150.000) - Biaya Riil (23.000) = 47.000
    $totalCosts = (float) $order->costs->sum('amount');
    $netProfit = (float) $order->total - (float) $order->cogs_total - $totalCosts;

    expect($totalCosts)->toBe(23000.0)
        ->and($netProfit)->toBe(47000.0);
});

test('staf gudang tidak memiliki izin viewFinancials untuk melihat margin laba', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staf_gudang');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($staff->can('viewFinancials', Order::class))->toBeFalse()
        ->and($admin->can('viewFinancials', Order::class))->toBeTrue();
});

test('pengaturan sistem dapat dibaca dan diperbarui dengan benar via Setting model', function () {
    Setting::set('shipping_due_hours', 5);
    Setting::set('payment_hold_hours', 24);

    expect(Setting::get('shipping_due_hours'))->toBe(5)
        ->and(Setting::get('payment_hold_hours'))->toBe(24);
});
