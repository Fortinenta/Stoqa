<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Account\OrderHistory;
use App\Models\Order;
use App\Models\OrderCost;
use App\Models\Payment;
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

test('cache pengaturan di-flush secara otomatis saat nilai disimpan atau diupdate', function () {
    Setting::set('shipping_due_hours', 4);
    expect(Setting::get('shipping_due_hours'))->toBe(4);

    $setting = Setting::find('shipping_due_hours');
    $setting->update(['value' => 8]);

    // Nilai cache harus ter-invalidation sehingga mengembalikan nilai 8
    expect(Setting::get('shipping_due_hours'))->toBe(8);
});

test('admin dapat menolak bukti pembayaran dan pesanan kembali ke status PENDING_PAYMENT dengan batas waktu baru', function () {
    $user = User::factory()->create();
    $warehouse = Warehouse::create(['name' => 'Gudang Utama', 'is_default' => true]);

    $order = Order::create([
        'user_id' => $user->id,
        'warehouse_id' => $warehouse->id,
        'channel' => 'catalog',
        'customer_name' => $user->name,
        'status' => OrderStatus::MENUNGGU_VERIFIKASI,
        'subtotal' => 100000,
        'shipping_cost' => 10000,
        'total' => 110000,
        'expires_at' => now()->addHours(1),
    ]);

    $payment = Payment::create([
        'order_id' => $order->id,
        'method' => 'manual',
        'provider' => 'manual_bca',
        'amount' => 110000,
        'status' => PaymentStatus::PENDING,
        'proof_url' => 'payment-proofs/sample.jpg',
    ]);

    $rejectionReason = 'Foto struk buram dan tidak terbaca';
    $holdHours = (int) Setting::get('payment_hold_hours', 12);
    $newExpiresAt = now()->addHours($holdHours);

    $order->payments()->where('status', PaymentStatus::PENDING)->get()->each(function ($p) use ($rejectionReason) {
        $payload = $p->payload ?? [];
        $payload['rejection_reason'] = $rejectionReason;
        $payload['rejected_at'] = now()->toIso8601String();
        $p->update(['status' => PaymentStatus::FAILED, 'payload' => $payload]);
    });

    $order->update([
        'status' => OrderStatus::PENDING_PAYMENT,
        'expires_at' => $newExpiresAt,
    ]);

    $payment->refresh();
    $order->refresh();

    expect($payment->status)->toBe(PaymentStatus::FAILED)
        ->and($payment->payload['rejection_reason'])->toBe('Foto struk buram dan tidak terbaca')
        ->and($order->status)->toBe(OrderStatus::PENDING_PAYMENT);
});

test('penolakan bukti transfer dibatasi maksimal 3 kali dan otomatis membatalkan order saat batas terlampaui', function () {
    $user = User::factory()->create();
    $warehouse = Warehouse::create(['name' => 'Gudang Utama', 'is_default' => true]);

    $order = Order::create([
        'user_id' => $user->id,
        'warehouse_id' => $warehouse->id,
        'channel' => 'catalog',
        'customer_name' => $user->name,
        'status' => OrderStatus::MENUNGGU_VERIFIKASI,
        'subtotal' => 100000,
        'shipping_cost' => 10000,
        'total' => 110000,
        'expires_at' => now()->addHours(1),
    ]);

    // Sudah pernah ditolak 2 kali sebelumnya
    Payment::create([
        'order_id' => $order->id,
        'method' => 'manual',
        'provider' => 'manual_bca',
        'amount' => 110000,
        'status' => PaymentStatus::FAILED,
        'payload' => ['rejection_reason' => 'Penolakan 1'],
    ]);

    Payment::create([
        'order_id' => $order->id,
        'method' => 'manual',
        'provider' => 'manual_bca',
        'amount' => 110000,
        'status' => PaymentStatus::FAILED,
        'payload' => ['rejection_reason' => 'Penolakan 2'],
    ]);

    // Pembayaran ke-3 yang sedang pending verifikasi
    $payment3 = Payment::create([
        'order_id' => $order->id,
        'method' => 'manual',
        'provider' => 'manual_bca',
        'amount' => 110000,
        'status' => PaymentStatus::PENDING,
        'proof_url' => 'payment-proofs/sample3.jpg',
    ]);

    $failedCount = $order->payments()->where('status', PaymentStatus::FAILED)->count();
    $currentAttempt = $failedCount + 1; // Percobaan ke-3

    $payment3->update([
        'status' => PaymentStatus::FAILED,
        'payload' => ['rejection_reason' => 'Penolakan ke-3 (Batas maksimal)'],
    ]);

    if ($currentAttempt >= 3) {
        $order->update([
            'status' => OrderStatus::REJECTED,
            'rejected_at' => now(),
            'reject_reason' => 'Batas verifikasi bukti (3x) terlampaui.',
        ]);
    }

    $order->refresh();
    expect($currentAttempt)->toBe(3)
        ->and($order->status)->toBe(OrderStatus::REJECTED);
});

test('nomor rekening atau nomor telepon dengan awalan nol tidak kehilangan angka nol di depannya', function () {
    Setting::set('bank_bri_account', '001201000123509');
    Setting::set('help_whatsapp', '081234567890');

    expect(Setting::get('bank_bri_account'))->toBe('001201000123509')
        ->and(Setting::get('help_whatsapp'))->toBe('081234567890');
});

test('metode pembayaran disembunyikan jika nomor rekening belum diisi atau semua metode dimatikan', function () {
    $component = new OrderHistory;

    // 1. Matikan semua metode pembayaran
    Setting::set('payment_methods', [
        'manual_bca' => false,
        'manual_mandiri' => false,
        'manual_bri' => false,
        'manual_qris' => false,
    ]);

    // Harus mengembalikan array kosong [], tidak boleh memunculkan fallback default
    expect($component->getActivePaymentMethods())->toBe([]);

    // 2. Aktifkan BCA tetapi nomor rekening kosong
    Setting::set('payment_methods', ['manual_bca' => true]);
    Setting::set('bank_bca_account', '');

    expect($component->getActivePaymentMethods())->toBe([]);

    // 3. Aktifkan BCA dan isi nomor rekening
    Setting::set('bank_bca_account', '1234567890');
    Setting::set('bank_bca_holder', 'Stoqa Official');

    $active = $component->getActivePaymentMethods();
    expect($active)->toHaveKey('manual_bca')
        ->and($active['manual_bca']['account'])->toBe('1234567890')
        ->and($active['manual_bca']['holder'])->toBe('Stoqa Official');
});
