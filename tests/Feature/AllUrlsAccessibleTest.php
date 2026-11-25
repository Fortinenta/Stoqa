<?php

namespace Tests\Feature;

use App\Livewire\Account\OrderHistory;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DummyDataSeeder;
use Database\Seeders\InitialDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AllUrlsAccessibleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialDataSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(DummyDataSeeder::class);
    }

    public function test_public_urls_are_accessible(): void
    {
        // 1. Katalog Beranda
        $response = $this->get('/');
        $response->assertStatus(200);

        // 2. Detail Produk
        $response = $this->get('/produk/SNI-2L');
        $response->assertStatus(200);

        // 3. Keranjang
        $response = $this->get('/keranjang');
        $response->assertStatus(200);

        // 4. Halaman Login
        $response = $this->get('/login');
        $response->assertStatus(200);

        // 5. Halaman Register
        $response = $this->get('/register');
        $response->assertStatus(200);

        // 6. Filament Admin Login
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    public function test_authenticated_urls_are_accessible(): void
    {
        $user = User::where('email', 'budi@example.com')->first();
        $this->actingAs($user);

        // 7. Pesanan Saya (Order History)
        $response = $this->get('/pesanan-saya');
        $response->assertStatus(200);

        // Test Livewire component OrderHistory filter tabs
        Livewire::test(OrderHistory::class)
            ->call('filterByStatus', 'pending')
            ->assertSet('statusFilter', 'pending')
            ->call('filterByStatus', 'paid')
            ->assertSet('statusFilter', 'paid')
            ->call('filterByStatus', 'cancelled')
            ->assertSet('statusFilter', 'cancelled')
            ->call('filterByStatus', 'all')
            ->assertSet('statusFilter', 'all');

        // 8. Checkout (saat ada item)
        $cartService = app(CartService::class);
        $prod = Product::where('sku', 'SNI-2L')->first();
        $unit = $prod->units()->first();
        $cartService->add($prod->id, [$unit->id => 1]);

        $response = $this->get('/checkout');
        $response->assertStatus(200);
    }

    public function test_filament_admin_resource_pages_are_accessible(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $this->actingAs($admin);

        // Dashboard
        $this->get('/admin')->assertStatus(200);

        // Product list, create, & edit
        $this->get('/admin/products')->assertStatus(200);
        $this->get('/admin/products/create')->assertStatus(200);
        $productIds = Product::orderBy('id')->take(2)->pluck('id');
        $this->get("/admin/products/{$productIds[0]}/edit")->assertStatus(200);
        $this->get("/admin/products/{$productIds[1]}/edit")->assertStatus(200);

        // Inbound Orders list & create
        $this->get('/admin/inbound-orders')->assertStatus(200);
        $this->get('/admin/inbound-orders/create')->assertStatus(200);

        // Orders list & create
        $this->get('/admin/orders')->assertStatus(200);
        $this->get('/admin/orders/create')->assertStatus(200);

        // Stock movements
        $this->get('/admin/stock-movements')->assertStatus(200);

        // Categories, Suppliers, Warehouses, Customer Prices
        $this->get('/admin/categories')->assertStatus(200);
        $this->get('/admin/suppliers')->assertStatus(200);
        $this->get('/admin/warehouses')->assertStatus(200);
        $this->get('/admin/customer-prices')->assertStatus(200);
    }
}
