<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Enums\ReservationStatus;
use App\Models\Order;
use App\Models\Setting;
use App\Models\StockReservation;
use App\Services\ReservationService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('No. Invoice')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('customer_info')
                    ->label('Pelanggan')
                    ->state(function (Order $record) {
                        return $record->user?->name ?? $record->customer_name ?? 'Tamu';
                    })
                    ->searchable(),
                TextColumn::make('channel')
                    ->label('Jalur')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'catalog' => 'info',
                        'manual' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::MENUNGGU_ONGKIR => 'warning',
                        OrderStatus::PENDING_PAYMENT => 'info',
                        OrderStatus::PAID, OrderStatus::COMPLETED => 'success',
                        OrderStatus::REJECTED, OrderStatus::CANCELLED, OrderStatus::EXPIRED => 'danger',
                    }),
                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('shipping_cost')
                    ->label('Ongkir')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total Bayar')
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('cogs_total')
                    ->label('HPP (Modal)')
                    ->money('IDR', locale: 'id')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('gross_profit')
                    ->label('Laba Kotor')
                    ->state(function (Order $record) {
                        if ($record->cogs_total === null) {
                            return null;
                        }

                        return (float) $record->subtotal - (float) $record->cogs_total;
                    })
                    ->money('IDR', locale: 'id')
                    ->color('success')
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Waktu Masuk')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        OrderStatus::MENUNGGU_ONGKIR->value => 'Menunggu Ongkir',
                        OrderStatus::PENDING_PAYMENT->value => 'Menunggu Pembayaran',
                        OrderStatus::PAID->value => 'Sudah Dibayar',
                        OrderStatus::COMPLETED->value => 'Selesai',
                        OrderStatus::REJECTED->value => 'Ditolak',
                        OrderStatus::EXPIRED->value => 'Kedaluwarsa',
                    ]),
            ])
            ->recordActions([
                // 1. Aksi Penentuan Ongkir (Batas 3 Jam)
                Action::make('setShipping')
                    ->label('Isi Ongkir')
                    ->icon('heroicon-o-truck')
                    ->color('primary')
                    ->visible(fn (Order $record) => $record->status === OrderStatus::MENUNGGU_ONGKIR)
                    ->form([
                        TextInput::make('shipping_cost')
                            ->label('Nominal Ongkos Kirim')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data) {
                        $shipping = (float) $data['shipping_cost'];
                        $newTotal = (float) $record->subtotal + $shipping + (float) $record->other_cost;

                        $holdHours = (int) (Setting::get('payment_hold_hours', 12));
                        $expiresAt = now()->addHours($holdHours);

                        // Generate invoice_no
                        $invoiceNo = 'INV/'.now()->format('Y/m').'/'.str_pad($record->id, 5, '0', STR_PAD_LEFT);

                        $record->update([
                            'shipping_cost' => $shipping,
                            'total' => $newTotal,
                            'status' => OrderStatus::PENDING_PAYMENT,
                            'invoice_no' => $invoiceNo,
                            'invoiced_at' => now(),
                            'expires_at' => $expiresAt,
                        ]);

                        // Perpanjang masa hold di stock_reservations
                        StockReservation::where('order_id', $record->id)
                            ->where('status', ReservationStatus::ACTIVE)
                            ->update(['expires_at' => $expiresAt]);

                        Notification::make()
                            ->title('Invoice Diterbitkan')
                            ->body("Invoice {$invoiceNo} diterbitkan dengan ongkir Rp ".number_format($shipping, 0, ',', '.'))
                            ->success()
                            ->send();
                    }),

                // 2. Aksi Tolak Pesanan
                Action::make('rejectOrder')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $record) => $record->status === OrderStatus::MENUNGGU_ONGKIR)
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reject_reason')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data) {
                        $reservationService = app(ReservationService::class);
                        $reservationService->release($record->id, ReservationStatus::RELEASED);

                        $record->update([
                            'status' => OrderStatus::REJECTED,
                            'rejected_at' => now(),
                            'reject_reason' => $data['reject_reason'],
                        ]);

                        Notification::make()
                            ->title('Pesanan Ditolak')
                            ->body('Pesanan ditolak dan reservasi stok telah dilepaskan.')
                            ->warning()
                            ->send();
                    }),

                // 3. Aksi Konfirmasi Pembayaran
                Action::make('confirmPayment')
                    ->label('Konfirmasi Bayar')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Order $record) => $record->status === OrderStatus::PENDING_PAYMENT)
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pembayaran Order')
                    ->modalDescription('Apakah Anda yakin pembayaran telah valid? Tindakan ini akan memotong saldo stok fisik batch FIFO secara permanen dan mencatat HPP serta laba.')
                    ->action(function (Order $record) {
                        $reservationService = app(ReservationService::class);
                        $reservationService->consume($record, auth()->id());

                        Notification::make()
                            ->title('Pembayaran Dikonfirmasi')
                            ->body('Stok FIFO telah dipotong dan HPP/Laba telah tercatat.')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ]);
    }
}
