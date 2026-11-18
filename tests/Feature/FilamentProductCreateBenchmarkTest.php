<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\InitialDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentProductCreateBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialDataSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->admin = User::where('email', 'admin@example.com')->first();
        $this->category = Category::create(['name' => 'Kebutuhan Dapur', 'slug' => 'kebutuhan-dapur']);
    }

    public function test_page_loads_with_optimal_queries_and_performance(): void
    {
        $this->actingAs($this->admin);

        $startTime = microtime(true);
        $response = $this->get('/admin/products/create');
        $duration = microtime(true) - $startTime;

        $response->assertStatus(200);
        // Halaman create form harus memuat dalam waktu cepat (< 1 detik pada pengujian lokal)
        $this->assertLessThan(1.5, $duration, "Waktu rendering halaman create product terlalu lambat: {$duration}s");
    }

    public function test_normal_create_product_success(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'sku' => 'TEST-PROD-01',
                'name' => 'Kecap Manis Botol 600ml',
                'category_id' => $this->category->id,
                'base_unit' => 'Botol',
                'min_stock' => 15,
                'is_active' => true,
                'show_price_public' => true,
                'description' => 'Kecap manis kedelai hitam pilihan.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Verifikasi record di database
        $product = Product::where('sku', 'TEST-PROD-01')->first();
        $this->assertNotNull($product);
        $this->assertEquals('Kecap Manis Botol 600ml', $product->name);
        $this->assertEquals('Botol', $product->base_unit);

        // Verifikasi otomatis dibuatkan base unit & stock level
        $this->assertDatabaseHas('product_units', [
            'product_id' => $product->id,
            'unit_name' => 'Botol',
            'conversion_to_base' => 1,
        ]);

        $this->assertDatabaseHas('stock_levels', [
            'product_id' => $product->id,
            'warehouse_id' => 1,
            'on_hand' => 0,
            'reserved' => 0,
        ]);
    }

    public function test_abnormal_empty_required_fields_fails_validation(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'sku' => '',
                'name' => '',
                'base_unit' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['sku' => 'required', 'name' => 'required', 'base_unit' => 'required']);
    }

    public function test_abnormal_duplicate_sku_fails_validation(): void
    {
        $this->actingAs($this->admin);

        Product::create([
            'sku' => 'DUPLICATE-SKU',
            'name' => 'Produk Awal',
            'base_unit' => 'pcs',
            'category_id' => $this->category->id,
        ]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'sku' => 'DUPLICATE-SKU',
                'name' => 'Produk Kedua',
                'base_unit' => 'pcs',
            ])
            ->call('create')
            ->assertHasFormErrors(['sku' => 'unique']);
    }

    public function test_abnormal_negative_or_invalid_values_are_rejected(): void
    {
        $this->actingAs($this->admin);

        // 1. Min stock negatif
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'sku' => 'TEST-NEG-01',
                'name' => 'Produk Negatif',
                'base_unit' => 'pcs',
                'min_stock' => -5,
            ])
            ->call('create')
            ->assertHasFormErrors(['min_stock']);

        // 2. SKU melebihi 64 karakter
        $longSku = str_repeat('A', 65);
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'sku' => $longSku,
                'name' => 'Produk SKU Panjang',
                'base_unit' => 'pcs',
                'min_stock' => 10,
            ])
            ->call('create')
            ->assertHasFormErrors(['sku' => 'max']);
    }

    public function test_benchmark_database_query_count_on_create_page(): void
    {
        $this->actingAs($this->admin);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get('/admin/products/create');
        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Memastikan query ringan dan efisien (di bawah 15 query untuk inisialisasi panel & auth)
        $this->assertLessThan(15, $queryCount, "Terlalu banyak query database saat membuka form create: {$queryCount} queries");
    }
}
