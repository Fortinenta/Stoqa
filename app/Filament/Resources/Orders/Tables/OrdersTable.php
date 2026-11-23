<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Order;
use App\Models\Setting;
use App\Models\StockReservation;
use App\Services\ReservationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['payments', 'user', 'costs']))
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
                    ->color(fn (OrderStatus $state): string => $state->color())
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label()),
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
                TextColumn::make('payment_proof')
                    ->label('Bukti Transfer')
                    ->badge()
                    ->state(function (Order $record) {
                        $hasProof = $record->payments->contains(fn ($p) => ! empty($p->proof_url));
                        if ($hasProof) {
                            return 'Ada Bukti';
                        }

                        return in_array($record->status, [OrderStatus::PENDING_PAYMENT, OrderStatus::MENUNGGU_VERIFIKASI, OrderStatus::MENUNGGU_ONGKIR])
                            ? 'Belum Ada'
                            : '-';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Ada Bukti' => 'success',
                        'Belum Ada' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('cogs_total')
                    ->label('HPP (Modal)')
                    ->money('IDR', locale: 'id')
                    ->placeholder('-')
                    ->visible(fn () => auth()->user()?->can('viewFinancials', Order::class) ?? false)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('gross_profit')
                    ->label('Laba Kotor')
                    ->visible(fn () => auth()->user()?->can('viewFinancials', Order::class) ?? false)
                    ->state(function (Order $record) {
                        if ($record->cogs_total === null) {
                            return null;
                        }

                        return (float) $record->subtotal - (float) $record->cogs_total;
                    })
                    ->money('IDR', locale: 'id')
                    ->color('success')
                    ->placeholder('-'),
                TextColumn::make('net_profit')
                    ->label('Laba Bersih')
                    ->visible(fn () => auth()->user()?->can('viewFinancials', Order::class) ?? false)
                    ->state(function (Order $record) {
                        if ($record->cogs_total === null) {
                            return null;
                        }

                        $totalCosts = (int) round($record->costs->sum('amount'));

                        return (int) round($record->total) - (int) round($record->cogs_total) - $totalCosts;
                    })
                    ->description(function (Order $record): ?string {
                        if ($record->cogs_total === null) {
                            return null;
                        }

                        if ($record->costs->isEmpty() && (int) round($record->shipping_cost) > 0) {
                            return 'Estimasi (Biaya riil belum diisi)';
                        }

                        return null;
                    })
                    ->money('IDR', locale: 'id')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
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
                        OrderStatus::MENUNGGU_VERIFIKASI->value => 'Menunggu Verifikasi',
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
                        try {
                            DB::transaction(function () use ($record, $data) {
                                $lockedOrder = Order::where('id', $record->id)->lockForUpdate()->firstOrFail();

                                if ($lockedOrder->status !== OrderStatus::MENUNGGU_ONGKIR) {
                                    throw new \RuntimeException('Status pesanan telah berubah.');
                                }

                                $hasActiveRes = StockReservation::where('order_id', $lockedOrder->id)
                                    ->where('status', ReservationStatus::ACTIVE)
                                    ->exists();

                                if (! $hasActiveRes) {
                                    throw new \RuntimeException('Masa tahan (hold) pesanan ini telah kedaluwarsa.');
                                }

                                $shipping = (float) $data['shipping_cost'];
                                $newTotal = (float) $lockedOrder->subtotal + $shipping + (float) $lockedOrder->other_cost;

                                $holdHours = (int) (Setting::get('payment_hold_hours', 12));
                                $expiresAt = now()->addHours($holdHours);

                                $invoiceNo = $lockedOrder->invoice_no ?: ('INV/'.now()->format('Y/m').'/'.str_pad($lockedOrder->id, 5, '0', STR_PAD_LEFT));

                                $lockedOrder->update([
                                    'shipping_cost' => $shipping,
                                    'total' => $newTotal,
                                    'status' => OrderStatus::PENDING_PAYMENT,
                                    'invoice_no' => $invoiceNo,
                                    'invoiced_at' => now(),
                                    'expires_at' => $expiresAt,
                                ]);

                                // Perpanjang masa hold di stock_reservations
                                StockReservation::where('order_id', $lockedOrder->id)
                                    ->where('status', ReservationStatus::ACTIVE)
                                    ->update(['expires_at' => $expiresAt]);
                            });

                            Notification::make()
                                ->title('Invoice Diterbitkan')
                                ->body('Ongkir berhasil disimpan dan status pesanan kini menunggu pembayaran.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            report($e);
                            Notification::make()
                                ->title('Gagal Memproses Ongkir')
                                ->body('Terjadi kendala saat memperbarui ongkir. Pastikan nominal ongkir valid.')
                                ->danger()
                                ->send();
                        }
                    }),

                // 2. Aksi Tolak Pesanan
                Action::make('rejectOrder')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $record) => in_array($record->status, [OrderStatus::MENUNGGU_ONGKIR, OrderStatus::PENDING_PAYMENT, OrderStatus::MENUNGGU_VERIFIKASI]))
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reject_reason')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data) {
                        try {
                            DB::transaction(function () use ($record, $data) {
                                $lockedOrder = Order::where('id', $record->id)->lockForUpdate()->firstOrFail();

                                if (! in_array($lockedOrder->status, [OrderStatus::MENUNGGU_ONGKIR, OrderStatus::PENDING_PAYMENT, OrderStatus::MENUNGGU_VERIFIKASI])) {
                                    throw new \RuntimeException('Pesanan tidak dapat ditolak karena berstatus: '.$lockedOrder->status->label());
                                }

                                $reservationService = app(ReservationService::class);
                                $reservationService->release($lockedOrder->id, ReservationStatus::RELEASED);

                                $lockedOrder->update([
                                    'status' => OrderStatus::REJECTED,
                                    'rejected_at' => now(),
                                    'reject_reason' => $data['reject_reason'],
                                ]);
                            });

                            Notification::make()
                                ->title('Pesanan Ditolak')
                                ->body('Pesanan ditolak dan reservasi stok telah dilepaskan.')
                                ->warning()
                                ->send();
                        } catch (\Throwable $e) {
                            report($e);
                            Notification::make()
                                ->title('Gagal Menolak Pesanan')
                                ->body('Terjadi kendala sistem saat menolak pesanan.')
                                ->danger()
                                ->send();
                        }
                    }),

                // 3. Aksi Lihat Bukti Pembayaran
                Action::make('viewProof')
                    ->label('Lihat Bukti')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn (Order $record) => $record->payments->contains(fn ($p) => ! empty($p->proof_url)))
                    ->modalHeading(fn (Order $record) => 'Bukti Transfer - '.($record->invoice_no ?: ('Order #'.$record->id)))
                    ->modalContent(fn (Order $record) => view('filament.resources.orders.confirm-payment-modal', [
                        'order' => $record,
                        'isViewOnly' => true,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                // 4. Aksi Konfirmasi Pembayaran
                Action::make('confirmPayment')
                    ->label('Konfirmasi Bayar')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Order $record) => in_array($record->status, [OrderStatus::PENDING_PAYMENT, OrderStatus::MENUNGGU_VERIFIKASI]))
                    ->requiresConfirmation()
                    ->modalHeading('Verifikasi & Konfirmasi Pembayaran Order')
                    ->modalContent(fn (Order $record) => view('filament.resources.orders.confirm-payment-modal', ['order' => $record->loadMissing('payments')]))
                    ->action(function (Order $record) {
                        try {
                            $reservationService = app(ReservationService::class);
                            $reservationService->consume($record, auth()->id());

                            // Update status pembayaran yang diunggah
                            $record->payments()
                                ->where('status', PaymentStatus::PENDING)
                                ->update([
                                    'status' => PaymentStatus::PAID,
                                    'confirmed_by' => auth()->id(),
                                    'confirmed_at' => now(),
                                ]);

                            Notification::make()
                                ->title('Pembayaran Dikonfirmasi')
                                ->body('Stok FIFO telah dipotong dan HPP/Laba telah tercatat.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            report($e);
                            Notification::make()
                                ->title('Gagal Konfirmasi Pembayaran')
                                ->body('Terjadi kendala saat mengonfirmasi pembayaran. Pastikan data stok pesanan valid.')
                                ->danger()
                                ->send();
                        }
                    }),

                // 4b. Aksi Tolak Bukti Transfer (Bukti Tidak Valid)
                Action::make('rejectPaymentProof')
                    ->label('Bukti Tidak Valid')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->visible(fn (Order $record) => $record->status === OrderStatus::MENUNGGU_VERIFIKASI)
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Bukti Transfer / Bukti Tidak Valid')
                    ->modalDescription('Tolak bukti transfer yang buram, nominal tidak sesuai, atau salah rekening. Status pesanan akan dikembalikan ke Menunggu Pembayaran dan batas bayar diperpanjang agar pembeli dapat mengunggah ulang.')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan / Catatan untuk Pembeli')
                            ->placeholder('Contoh: Foto struk transfer buram/terpotong atau nominal transfer kurang.')
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data) {
                        try {
                            DB::transaction(function () use ($record, $data) {
                                $lockedOrder = Order::where('id', $record->id)->lockForUpdate()->firstOrFail();

                                if ($lockedOrder->status !== OrderStatus::MENUNGGU_VERIFIKASI) {
                                    throw new \RuntimeException('Pesanan ini tidak sedang menunggu verifikasi.');
                                }

                                $holdHours = (int) Setting::get('payment_hold_hours', 12);
                                $newExpiresAt = now()->addHours($holdHours);

                                foreach ($lockedOrder->payments()->where('status', PaymentStatus::PENDING)->get() as $payment) {
                                    $payload = $payment->payload ?? [];
                                    $payload['rejection_reason'] = $data['rejection_reason'];
                                    $payload['rejected_at'] = now()->toIso8601String();

                                    $payment->update([
                                        'status' => PaymentStatus::FAILED,
                                        'payload' => $payload,
                                    ]);
                                }

                                $lockedOrder->update([
                                    'status' => OrderStatus::PENDING_PAYMENT,
                                    'expires_at' => $newExpiresAt,
                                ]);

                                StockReservation::where('order_id', $lockedOrder->id)
                                    ->where('status', ReservationStatus::ACTIVE)
                                    ->update(['expires_at' => $newExpiresAt]);
                            });

                            Notification::make()
                                ->title('Bukti Pembayaran Ditolak')
                                ->body('Status pesanan dikembalikan ke Menunggu Pembayaran dan pembeli dapat mengunggah struk baru.')
                                ->warning()
                                ->send();
                        } catch (\Throwable $e) {
                            report($e);
                            Notification::make()
                                ->title('Gagal Menolak Bukti Pembayaran')
                                ->body('Terjadi kendala sistem saat menolak bukti pembayaran.')
                                ->danger()
                                ->send();
                        }
                    }),

                // 5. Aksi Input & Kelola Biaya Riil Operasional (Ongkir Keluar, Packing, dll.)
                Action::make('manageCosts')
                    ->label('Biaya Riil')
                    ->icon('phosphor-coins-duotone')
                    ->color('warning')
                    ->visible(fn (Order $record) => in_array($record->status, [OrderStatus::PAID, OrderStatus::SHIPPED, OrderStatus::COMPLETED]) && (auth()->user()?->can('viewFinancials', Order::class) ?? false))
                    ->modalHeading(fn (Order $record) => 'Biaya Riil Operasional - '.($record->invoice_no ?: ('Order #'.$record->id)))
                    ->modalDescription('Catat pengeluaran nyata toko (ongkir riil ke kurir, biaya packing, dll.) untuk menghitung laba bersih secara akurat.')
                    ->fillForm(function (Order $record): array {
                        $shippingOut = $record->costs->where('type', 'shipping_out')->first();
                        $packing = $record->costs->where('type', 'packing')->first();
                        $other = $record->costs->where('type', 'other')->first();

                        return [
                            'shipping_out' => $shippingOut ? (float) $shippingOut->amount : 0,
                            'packing' => $packing ? (float) $packing->amount : 0,
                            'other' => $other ? (float) $other->amount : 0,
                            'note' => $shippingOut?->note ?? $other?->note ?? '',
                        ];
                    })
                    ->form([
                        TextInput::make('shipping_out')
                            ->label('Ongkir Riil Ekspedisi / Kurir')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->required(),
                        TextInput::make('packing')
                            ->label('Biaya Packing & Bubble Wrap')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        TextInput::make('other')
                            ->label('Biaya Lain-lain (Komisi/Operasional)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        TextInput::make('note')
                            ->label('Catatan Ekspedisi / Resi')
                            ->maxLength(255),
                    ])
                    ->action(function (Order $record, array $data) {
                        try {
                            DB::transaction(function () use ($record, $data) {
                                $types = [
                                    'shipping_out' => (float) ($data['shipping_out'] ?? 0),
                                    'packing' => (float) ($data['packing'] ?? 0),
                                    'other' => (float) ($data['other'] ?? 0),
                                ];

                                foreach ($types as $type => $amount) {
                                    if ($amount > 0) {
                                        $record->costs()->updateOrCreate(
                                            ['type' => $type],
                                            [
                                                'amount' => $amount,
                                                'note' => $data['note'] ?? null,
                                            ]
                                        );
                                    } else {
                                        $record->costs()->where('type', $type)->delete();
                                    }
                                }
                            });

                            Notification::make()
                                ->title('Biaya Riil Disimpan')
                                ->body('Pengeluaran nyata telah diperbarui dan laba bersih telah dihitung ulang.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            report($e);
                            Notification::make()
                                ->title('Gagal Menyimpan Biaya Riil')
                                ->body('Terjadi kendala saat menyimpan pengeluaran riil pesanan. Pastikan nominal valid.')
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}
