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
            'bank_bca_account' => '',
            'bank_bca_holder' => '',
            'bank_mandiri_account' => '',
            'bank_mandiri_holder' => '',
            'bank_bri_account' => '',
            'bank_bri_holder' => '',
            'qris_nmid' => '',
            'qris_holder' => '',
            'help_whatsapp' => '',
            'payment_methods' => [
                'system_enabled' => true,
                'manual_enabled' => false,
                'channels' => [
                    'manual_bca' => false,
                    'manual_mandiri' => false,
                    'manual_bri' => false,
                    'manual_qris' => false,
                ],
                'gateway_enabled' => false,
                'gateway_provider' => 'midtrans',
                'gateway_mode' => 'sandbox',
            ],
            'low_stock_alert' => true,
        ];

        foreach ($defaults as $key => $value) {
            DB::table('settings')->insertOrIgnore([
                'key' => $key,
                'value' => json_encode($value),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('warehouses', 'id'), coalesce((SELECT MAX(id) FROM warehouses), 1))");
        }
    }
}
