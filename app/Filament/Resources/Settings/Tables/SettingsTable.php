<?php

namespace App\Filament\Resources\Settings\Tables;

use App\Models\Setting;
use Filament\Actions\EditAction;
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
                        'help_whatsapp' => 'Nomor kontak WhatsApp admin/toko untuk bantuan pelanggan.',
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
                        'help_whatsapp' => 'WhatsApp Bantuan / CS',
                        default => $record->key,
                    })
                    ->form(function (Setting $record): array {
                        if ($record->key === 'shipping_due_hours' || $record->key === 'payment_hold_hours') {
                            return [
                                TextInput::make('value')
                                    ->label('Durasi Waktu')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(720)
                                    ->suffix('Jam')
                                    ->default($record->value)
                                    ->required(),
                            ];
                        }

                        return [
                            TextInput::make('value')
                                ->label('Nilai Pengaturan')
                                ->default(is_array($record->value) ? json_encode($record->value) : (string) $record->value)
                                ->required(),
                        ];
                    })
                    ->action(function (Setting $record, array $data) {
                        $val = is_numeric($data['value']) ? (int) $data['value'] : $data['value'];
                        $record->update(['value' => $val]);

                        Notification::make()
                            ->title('Pengaturan Berhasil Disimpan')
                            ->body("Konfigurasi parameter {$record->key} kini bernilai: {$data['value']}.")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
