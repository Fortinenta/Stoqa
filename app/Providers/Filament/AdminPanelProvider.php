<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\CriticalStockTableWidget;
use App\Filament\Widgets\InventoryOverviewWidget;
use App\Filament\Widgets\RecentOrdersTableWidget;
use App\Filament\Widgets\SalesRevenueChartWidget;
use App\Filament\Widgets\StockMovementChartWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Stoqa')
            ->brandLogo(asset('images/stoqa-logo.svg'))
            ->darkModeBrandLogo(asset('images/stoqa-logo-dark.svg'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('images/stoqa-icon.svg'))
            ->font('Nunito')
            ->colors([
                'primary' => [
                    50 => '239, 250, 248',
                    100 => '215, 243, 238',
                    200 => '177, 230, 222',
                    300 => '130, 211, 201',
                    400 => '82, 185, 174',
                    500 => '52, 160, 149',
                    600 => '39, 129, 119',
                    700 => '35, 103, 95',
                    800 => '32, 83, 77',
                    900 => '30, 70, 66',
                    950 => '12, 42, 40',
                ],
                'gray' => [
                    50 => '246, 248, 247',
                    100 => '237, 241, 240',
                    200 => '221, 228, 226',
                    300 => '195, 206, 203',
                    400 => '148, 165, 161',
                    500 => '107, 126, 122',
                    600 => '81, 99, 95',
                    700 => '63, 78, 75',
                    800 => '42, 55, 53',
                    900 => '31, 45, 43',
                    950 => '18, 28, 27',
                ],
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'info' => Color::Sky,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Penjualan'),
                NavigationGroup::make('Etalase'),
                NavigationGroup::make('Gudang'),
                NavigationGroup::make('Pengaturan'),
            ])
            ->maxContentWidth('7xl')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                InventoryOverviewWidget::class,
                SalesRevenueChartWidget::class,
                StockMovementChartWidget::class,
                CriticalStockTableWidget::class,
                RecentOrdersTableWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
