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
            'payment_hold_hours' => 12,   // batas bayar sejak invoice terbit
            'shipping_due_hours' => 3,    // batas admin mengisi ongkir / menolak
            'payment_methods' => ['manual' => true, 'xendit' => false, 'midtrans' => false],
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
