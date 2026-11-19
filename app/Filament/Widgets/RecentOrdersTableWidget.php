<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentOrdersTableWidget extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Pesanan Penjualan Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()->with(['user'])->latest()
            )
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('No. Order')
                    ->default(fn (Order $record): string => 'Order #'.$record->id)
                    ->weight(FontWeight::Bold)
                    ->color('primary'),

                TextColumn::make('customer')
                    ->label('Pelanggan')
                    ->state(fn (Order $record): string => $record->customer_name ?: ($record->user?->name ?? 'Pembeli Umum'))
                    ->limit(20),

                TextColumn::make('channel')
                    ->label('Saluran')
                    ->badge()
                    ->color(fn ($state): string => $state === 'catalog' ? 'info' : 'gray')
                    ->formatStateUsing(fn ($state): string => $state === 'catalog' ? 'Katalog' : 'Manual'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => $state?->color() ?? 'gray')
                    ->formatStateUsing(fn ($state): string => $state?->label() ?? '-'),

                TextColumn::make('total')
                    ->label('Total Tagihan')
                    ->money('IDR', locale: 'id')
                    ->alignEnd(),

                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->since()
                    ->alignEnd(),
            ])
            ->actions([
                Action::make('view')
                    ->label('Detail')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (): string => OrderResource::getUrl('index')),
            ])
            ->emptyStateHeading('Belum Ada Pesanan')
            ->emptyStateDescription('Pesanan yang masuk melalui kasir atau katalog akan tampil di sini.')
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->paginated([5]);
    }
}
