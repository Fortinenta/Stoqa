<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\InitialDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentStockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Product $product;

    protected Warehouse $warehouse;

    protected ProductUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialDataSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->admin = User::where('email', 'admin@example.com')->first();
        $this->warehouse = Warehouse::first();

        $category = Category::create(['name' => 'Minuman', 'slug' => 'minuman']);

        $this->product = Product::create([
            'sku' => 'MNM-001',
            'name' => 'Sirup Marjan 460ml',
            'base_unit' => 'Botol',
            'category_id' => $category->id,
            'min_stock' => 5,
        ]);

        $this->unit = ProductUnit::create([
            'product_id' => $this->product->id,
            'unit_name' => 'Botol',
            'conversion_to_base' => 1,
        ]);

        // Masukkan stok awal 20 Botol @ Rp 18.000
        $stockService = app(StockService::class);
        $stockService->receive([
            'warehouse_id' => $this->warehouse->id,
            'received_date' => now()->toDateString(),
            'shipping_cost' => 0,
            'other_cost' => 0,
            'items' => [
                ['product_id' => $this->product->id, 'qty_base' => 20, 'unit_price' => 18000],
            ],
        ]);
    }

    public function test_admin_can_reduce_stock_for_damaged_items_directly(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListProducts::class)
            ->callTableAction('adjustStock', $this->product, [
                'warehouse_id' => $this->warehouse->id,
                'adjustment_type' => 'decrease',
                'reason_category' => 'Barang Rusak / Pecah',
                'unit_id' => $this->unit->id,
                'qty' => 3,
                'notes' => 'Pecah 3 botol saat diturunkan dari rak',
            ])
            ->assertHasNoTableActionErrors();

        // Verifikasi stok fisik berkurang dari 20 menjadi 17
        $stockLevel = StockLevel::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertEquals(17, $stockLevel->on_hand);
        $this->assertEquals(17, $stockLevel->available);

        // Verifikasi mutasi tercatat di ledger stock_movements
        $movement = StockMovement::where('product_id', $this->product->id)
            ->where('type', StockMovementType::ADJUST)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(-3, $movement->qty_base);
        $this->assertStringContainsString('Barang Rusak / Pecah', $movement->note);
        $this->assertStringContainsString('Pecah 3 botol', $movement->note);
    }

    public function test_admin_can_reduce_stock_for_gift_or_free_sample(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListProducts::class)
            ->callTableAction('adjustStock', $this->product, [
                'warehouse_id' => $this->warehouse->id,
                'adjustment_type' => 'decrease',
                'reason_category' => 'Hadiah / Promo / Gift',
                'unit_id' => $this->unit->id,
                'qty' => 2,
                'notes' => 'Gift bonus untuk pelanggan setia',
            ])
            ->assertHasNoTableActionErrors();

        $stockLevel = StockLevel::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertEquals(18, $stockLevel->on_hand);

        $movement = StockMovement::where('product_id', $this->product->id)
            ->where('type', StockMovementType::ADJUST)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(-2, $movement->qty_base);
        $this->assertStringContainsString('Hadiah / Promo / Gift', $movement->note);
    }
}
