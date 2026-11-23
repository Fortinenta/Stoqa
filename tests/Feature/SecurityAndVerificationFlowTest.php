<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockReservation;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ReservationService;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'staf_gudang', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

test('admin seeder melempar exception di environment production jika password kosong atau kurang dari 12 karakter', function () {
    app()->detectEnvironment(fn () => 'production');
    putenv('ADMIN_DEFAULT_PASSWORD=');

    expect(fn () => (new AdminUserSeeder)->run())
        ->toThrow(RuntimeException::class, 'ADMIN_DEFAULT_PASSWORD wajib disetel di environment production dengan minimal 12 karakter.');

    app()->detectEnvironment(fn () => 'testing');
});

test('rute bukti pembayaran memblokir akses publik tanpa login dan pelanggan lain', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->image('struk_bca.jpg');
    $path = $file->store('payment-proofs', 'local');

    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $warehouse = Warehouse::create(['name' => 'Gudang Utama', 'is_default' => true]);

    $order = Order::create([
        'user_id' => $owner->id,
        'warehouse_id' => $warehouse->id,
        'channel' => 'catalog',
        'customer_name' => $owner->name,
        'status' => OrderStatus::MENUNGGU_VERIFIKASI,
        'subtotal' => 100000,
        'shipping_cost' => 15000,
        'total' => 115000,
    ]);

    $payment = Payment::create([
        'order_id' => $order->id,
        'method' => 'manual',
        'provider' => 'manual_bca',
        'amount' => 115000,
        'status' => PaymentStatus::PENDING,
        'proof_url' => $path,
    ]);

    // 1. Tanpa login -> redirect / 401
    $guestResponse = $this->get(route('payment.proof.show', ['order' => $order->id, 'payment' => $payment->id]));
    $guestResponse->assertRedirect(route('login'));

    // 2. User lain yang bukan pemilik & bukan admin -> 403 Forbidden
    $strangerResponse = $this->actingAs($stranger)->get(route('payment.proof.show', ['order' => $order->id, 'payment' => $payment->id]));
    $strangerResponse->assertForbidden();

    // 3. Pemilik order -> 200 OK & Header nosniff
    $ownerResponse = $this->actingAs($owner)->get(route('payment.proof.show', ['order' => $order->id, 'payment' => $payment->id]));
    $ownerResponse->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    // 4. Admin -> 200 OK & Header nosniff
    $adminResponse = $this->actingAs($admin)->get(route('payment.proof.show', ['order' => $order->id, 'payment' => $payment->id]));
    $adminResponse->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('order dengan status MENUNGGU_VERIFIKASI dilindungi dari pelepasan stok otomatis oleh expireOverdue', function () {
    $user = User::factory()->create();
    $warehouse = Warehouse::create(['name' => 'Gudang Uji', 'is_default' => true]);
    $cat = Category::create(['name' => 'Kategori Uji']);
    $prod = Product::create([
        'category_id' => $cat->id,
        'sku' => 'PROD-HOLD-01',
        'name' => 'Produk Uji Proteksi Hold',
        'base_unit' => 'pcs',
        'min_stock' => 5,
        'is_active' => true,
    ]);

    StockLevel::create([
        'product_id' => $prod->id,
        'warehouse_id' => $warehouse->id,
        'on_hand' => 10,
        'reserved' => 5,
    ]);

    // Order 1: Masih PENDING_PAYMENT dan sudah lewat waktu -> harus kadaluarsa
    $expiredOrder = Order::create([
        'user_id' => $user->id,
        'warehouse_id' => $warehouse->id,
        'channel' => 'catalog',
        'customer_name' => $user->name,
        'status' => OrderStatus::PENDING_PAYMENT,
        'subtotal' => 50000,
        'total' => 50000,
        'expires_at' => now()->subHours(2),
    ]);

    StockReservation::create([
        'order_id' => $expiredOrder->id,
        'product_id' => $prod->id,
        'warehouse_id' => $warehouse->id,
        'qty_base' => 2,
        'status' => ReservationStatus::ACTIVE,
        'expires_at' => now()->subHours(2),
    ]);

    // Order 2: Sudah upload bukti bayar (MENUNGGU_VERIFIKASI) -> stok TIDAK boleh dilepas meskipun expires_at lewat
    $protectedOrder = Order::create([
        'user_id' => $user->id,
        'warehouse_id' => $warehouse->id,
        'channel' => 'catalog',
        'customer_name' => $user->name,
        'status' => OrderStatus::MENUNGGU_VERIFIKASI,
        'subtotal' => 75000,
        'total' => 75000,
        'expires_at' => now()->subHours(2),
    ]);

    $protectedReservation = StockReservation::create([
        'order_id' => $protectedOrder->id,
        'product_id' => $prod->id,
        'warehouse_id' => $warehouse->id,
        'qty_base' => 3,
        'status' => ReservationStatus::ACTIVE,
        'expires_at' => now()->subHours(2),
    ]);

    $reservationService = app(ReservationService::class);
    $processedCount = $reservationService->expireOverdue();

    // Tepat 1 reservasi yang di-expire (hanya Order 1)
    expect($processedCount)->toBe(1);

    // Order 1 menjadi EXPIRED
    expect($expiredOrder->fresh()->status)->toBe(OrderStatus::EXPIRED);

    // Order 2 tetap MENUNGGU_VERIFIKASI dan reservasinya tetap ACTIVE
    expect($protectedOrder->fresh()->status)->toBe(OrderStatus::MENUNGGU_VERIFIKASI);
    expect($protectedReservation->fresh()->status)->toBe(ReservationStatus::ACTIVE);

    // Sisa reserved harus tetap 3 untuk Order 2 (5 awal - 2 yang expired = 3)
    $level = StockLevel::where('product_id', $prod->id)->where('warehouse_id', $warehouse->id)->first();
    expect($level->reserved)->toBe(3);
});
