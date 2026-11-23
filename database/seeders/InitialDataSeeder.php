<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // Satu gudang default (struktur sudah siap multi-gudang)
        DB::table('warehouses')->insertOrIgnore([
            'id' => 1, 'name' => 'Gudang Utama', 'is_default' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $defaults = [
            'shipping_due_hours' => 3,    // batas admin mengisi ongkir / menolak (jam)
            'payment_hold_hours' => 12,   // batas bayar sejak invoice terbit (jam)
            'bank_bca_account' => '1234-5678-90',
            'bank_bca_holder' => 'PT Stoqa Niaga Bersama',
            'bank_mandiri_account' => '137-00-1234567-8',
            'bank_mandiri_holder' => 'PT Stoqa Niaga Bersama',
            'bank_bri_account' => '0012-01-000123-50-9',
            'bank_bri_holder' => 'PT Stoqa Niaga Bersama',
            'help_whatsapp' => '6281234567890',
            'payment_methods' => [
                'manual_bca' => true,
                'manual_mandiri' => true,
                'manual_bri' => true,
                'manual_qris' => false,
            ],
            'low_stock_alert' => true,
        ];

        foreach ($defaults as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => json_encode($value), 'created_at' => now(), 'updated_at' => now()]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('warehouses', 'id'), coalesce((SELECT MAX(id) FROM warehouses), 1))");
        }
    }
}
