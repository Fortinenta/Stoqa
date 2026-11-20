<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Buat Role utama
        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staf_gudang', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $adminPassword = env('ADMIN_DEFAULT_PASSWORD');

        if (app()->environment('production')) {
            if (empty($adminPassword) || strlen($adminPassword) < 12) {
                throw new \RuntimeException('ADMIN_DEFAULT_PASSWORD wajib disetel di environment production dengan minimal 12 karakter.');
            }
        } else {
            $adminPassword = $adminPassword ?: 'password';
        }

        // Buat User Admin default
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make($adminPassword),
                'is_active' => true,
            ]
        );

        $admin->assignRole($roleAdmin);
    }
}
