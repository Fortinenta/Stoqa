<?php

namespace App\Filament\Resources\Settings\Tables;

use App\Models\Setting;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('setting_label')
                    ->label('Nama Pengaturan')
                    ->state(fn (Setting $record): string => match ($record->key) {
                        'shipping_due_hours' => 'Batas Waktu Pengisian Ongkir (Admin)',
                        'payment_hold_hours' => 'Batas Waktu Pembayaran (Pelanggan)',
                        'bank_bca_account' => 'Nomor Rekening BCA',
                        'bank_bca_holder' => 'Atas Nama Rekening BCA',
                        'bank_mandiri_account' => 'Nomor Rekening Mandiri',
                        'bank_mandiri_holder' => 'Atas Nama Rekening Mandiri',
                        'bank_bri_account' => 'Nomor Rekening BRI',
                        'bank_bri_holder' => 'Atas Nama Rekening BRI',
                        'qris_nmid' => 'NMID QRIS Toko',
                        'qris_holder' => 'Atas Nama QRIS Toko',
                        'help_whatsapp' => 'Kontak WhatsApp Bantuan / CS',
                        'low_stock_alert' => 'Peringatan Stok Menipis',
                        'payment_methods' => 'Metode Pembayaran Aktif',
                        default => str_replace('_', ' ', ucwords($record->key, '_')),
                    })
                    ->description(fn (Setting $record): string => match ($record->key) {
                        'shipping_due_hours' => 'Durasi maksimal bagi admin gudang untuk menentukan tarif ongkir pesanan baru.',
                        'payment_hold_hours' => 'Durasi masa tahan (hold) reservasi stok bagi pembeli untuk menyelesaikan pembayaran.',
                        'bank_bca_account' => 'Nomor rekening bank BCA yang tampil di halaman pembayaran pembeli.',
                        'bank_mandiri_account' => 'Nomor rekening bank Mandiri yang tampil di halaman pembayaran pembeli.',
                        'bank_bri_account' => 'Nomor rekening bank BRI yang tampil di halaman pembayaran pembeli.',
                        'qris_nmid' => 'National Merchant ID (NMID) resmi untuk pembayaran via QRIS.',
                        'qris_holder' => 'Nama merchant atau pemilik rekening yang terdaftar pada QRIS.',
                        'help_whatsapp' => 'Nomor kontak WhatsApp admin/toko untuk bantuan pelanggan.',
                        'payment_methods' => 'Saklar metode transfer/pembayaran yang aktif dan dapat dipilih pembeli.',
                        default => 'Parameter konfigurasi operasional Stoqa.',
                    })
                    ->weight('bold')
                    ->searchable(query: fn ($query, $search) => $query->where('key', 'like', "%{$search}%")),

                TextColumn::make('key')
                    ->label('Kode Parameter')
                    ->badge()
                    ->color('gray')
                    ->fontFamily('mono'),

                TextColumn::make('display_value')
                    ->label('Nilai Saat Ini')
                    ->state(function (Setting $record): string {
                        if ($record->key === 'shipping_due_hours' || $record->key === 'payment_hold_hours') {
                            return $record->value.' Jam';
                        }
                        if ($record->key === 'payment_methods' && is_array($record->value)) {
                            $active = array_keys(array_filter($record->value));

                            return count($active) ? implode(', ', array_map(fn ($k) => strtoupper(str_replace('manual_', '', $k)), $active)) : 'Semua Nonaktif';
                        }
                        if (is_array($record->value)) {
                            return json_encode($record->value);
                        }

                        return (string) ($record->value ?? '-');
                    })
                    ->weight('black')
                    ->color('primary'),

                TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Ubah')
                    ->modalHeading(fn (Setting $record) => 'Ubah '.match ($record->key) {
                        'shipping_due_hours' => 'Batas Waktu Pengisian Ongkir',
                        'payment_hold_hours' => 'Batas Waktu Pembayaran',
                        'bank_bca_account' => 'Nomor Rekening BCA',
                        'bank_bca_holder' => 'Atas Nama Rekening BCA',
                        'bank_mandiri_account' => 'Nomor Rekening Mandiri',
                        'bank_mandiri_holder' => 'Atas Nama Rekening Mandiri',
                        'bank_bri_account' => 'Nomor Rekening BRI',
                        'bank_bri_holder' => 'Atas Nama Rekening BRI',
                        'qris_nmid' => 'NMID QRIS Toko',
                        'qris_holder' => 'Atas Nama QRIS Toko',
                        'help_whatsapp' => 'WhatsApp Bantuan / CS',
                        'payment_methods' => 'Metode Pembayaran Aktif',
                        default => $record->key,
                    })
                    ->form(function (Setting $record): array {
                        if ($record->key === 'shipping_due_hours' || $record->key === 'payment_hold_hours') {
                            return [
                                TextInput::make('value')
                                    ->label('Durasi Waktu (Jam)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(168)
                                    ->suffix('Jam')
                                    ->helperText('Batas durasi minimal 1 jam dan maksimal 168 jam (7 hari).')
                                    ->default($record->value)
                                    ->required(),
                            ];
                        }

                        if ($record->key === 'payment_methods') {
                            return [
                                CheckboxList::make('methods')
                                    ->label('Pilih Metode Pembayaran yang Aktif')
                                    ->options([
                                        'manual_bca' => 'Transfer Bank BCA',
                                        'manual_mandiri' => 'Transfer Bank Mandiri',
                                        'manual_bri' => 'Transfer Bank BRI',
                                        'manual_qris' => 'QRIS (Gopay, OVO, Dana, ShopeePay)',
                                    ])
                                    ->default(fn () => is_array($record->value) ? array_keys(array_filter($record->value)) : ['manual_bca', 'manual_mandiri', 'manual_bri'])
                                    ->required(),
                            ];
                        }

                        if ($record->key === 'help_whatsapp') {
                            return [
                                TextInput::make('value')
                                    ->label('Nomor WhatsApp Bantuan')
                                    ->tel()
                                    ->regex('/^[0-9+]{8,20}$/')
                                    ->helperText('Format nomor diawali dengan 62 atau +62, contoh: 6281234567890.')
                                    ->default((string) $record->value)
                                    ->required(),
                            ];
                        }

                        if ($record->key === 'qris_nmid') {
                            return [
                                TextInput::make('value')
                                    ->label('National Merchant ID (NMID)')
                                    ->helperText('Contoh: ID102003004050 atau kode NMID QRIS resmi.')
                                    ->maxLength(50)
                                    ->default((string) $record->value)
                                    ->required(),
                            ];
                        }

                        return [
                            TextInput::make('value')
                                ->label('Nilai Pengaturan')
                                ->maxLength(150)
                                ->default(is_array($record->value) ? json_encode($record->value) : (string) $record->value)
                                ->required(),
                        ];
                    })
                    ->action(function (Setting $record, array $data) {
                        if ($record->key === 'payment_methods') {
                            $selected = $data['methods'] ?? [];
                            $allMethods = ['manual_bca', 'manual_mandiri', 'manual_bri', 'manual_qris'];
                            $val = [];
                            foreach ($allMethods as $m) {
                                $val[$m] = in_array($m, $selected);
                            }
                            $record->update(['value' => $val]);
                        } elseif (in_array($record->key, ['shipping_due_hours', 'payment_hold_hours'])) {
                            // Cast integer hanya untuk jam agar angka nol di depan pada rekening/WA tidak hilang
                            $record->update(['value' => (int) $data['value']]);
                        } else {
                            $record->update(['value' => (string) $data['value']]);
                        }

                        Notification::make()
                            ->title('Pengaturan Berhasil Disimpan')
                            ->body("Konfigurasi parameter {$record->key} telah diperbarui.")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
