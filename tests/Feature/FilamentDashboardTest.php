<?php

namespace Tests\Feature;

use App\Filament\Widgets\CriticalStockTableWidget;
use App\Filament\Widgets\InventoryOverviewWidget;
use App\Filament\Widgets\RecentOrdersTableWidget;
use App\Filament\Widgets\SalesRevenueChartWidget;
use App\Filament\Widgets\StockMovementChartWidget;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DummyDataSeeder;
use Database\Seeders\InitialDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialDataSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(DummyDataSeeder::class);

        $this->admin = User::where('email', 'admin@example.com')->first();
    }

    public function test_admin_can_access_dashboard_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_inventory_overview_widget_renders_correctly(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(InventoryOverviewWidget::class)
            ->assertSuccessful()
            ->assertSee('Valuasi Persediaan')
            ->assertSee('Laba Kotor Bulan Ini')
            ->assertSee('Stok Tertahan (Hold)')
            ->assertSee('Peringatan Stok');
    }

    public function test_charts_and_tables_render_successfully(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(SalesRevenueChartWidget::class)
            ->assertSuccessful();

        Livewire::test(StockMovementChartWidget::class)
            ->assertSuccessful();

        Livewire::test(CriticalStockTableWidget::class)
            ->assertSuccessful();

        Livewire::test(RecentOrdersTableWidget::class)
            ->assertSuccessful();
    }
}
