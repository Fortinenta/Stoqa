<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Pesanan'),
            'menunggu_verifikasi' => Tab::make('Antrean Verifikasi')
                ->badge(fn () => Order::where('status', OrderStatus::MENUNGGU_VERIFIKASI)->count() ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::MENUNGGU_VERIFIKASI)),
            'menunggu_ongkir' => Tab::make('Perlu Ongkir')
                ->badge(fn () => Order::where('status', OrderStatus::MENUNGGU_ONGKIR)->count() ?: null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::MENUNGGU_ONGKIR)),
            'diproses' => Tab::make('Sudah Bayar')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [OrderStatus::PAID, OrderStatus::SHIPPED])),
        ];
    }
}
