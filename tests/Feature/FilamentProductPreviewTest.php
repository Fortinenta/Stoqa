<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DummyDataSeeder;
use Database\Seeders\InitialDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentProductPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialDataSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(DummyDataSeeder::class);

        $this->admin = User::where('email', 'admin@example.com')->first();
        // Minyak Sania 2L memiliki stok fisik 120 dan hold 12 dari Order A (Budi Santoso)
        $this->product = Product::where('sku', 'SNI-2L')->first();
    }

    public function test_admin_can_preview_product_details_and_hold_reservations(): void
    {
        $this->actingAs($this->admin);

        // Uji rendering Blade template preview-modal secara langsung
        $view = $this->view('filament.resources.products.preview-modal', [
            'product' => $this->product->loadMissing(['category', 'units', 'prices.unit', 'stockLevels', 'stockBatches']),
        ]);

        $view->assertSee($this->product->name);
        $view->assertSee($this->product->sku);
        $view->assertSee('120'); // Stok fisik
        $view->assertSee('12');  // Stok di-hold
        $view->assertSee('108'); // Stok tersedia
        $view->assertSee('Budi Santoso'); // Nama pembeli yang menahan stok
        $view->assertSee('Order #'); // Nomor order
        $view->assertSee('Rincian Pesanan yang Sedang Menahan Stok (Hold)');
        $view->assertSee('Rincian Batch Masuk');
        $view->assertSee('#BATCH-');

        // Pastikan action preview terpasang pada ListProducts
        Livewire::test(ListProducts::class)
            ->assertTableActionExists('preview');
    }
}
