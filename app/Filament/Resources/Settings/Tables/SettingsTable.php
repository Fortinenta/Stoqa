<?php

namespace App\Filament\Resources\Settings\Tables;

use App\Models\Setting;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
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
                            $cfg = $record->value;
                            $systemEnabled = $cfg['system_enabled'] ?? true;
                            if (! $systemEnabled) {
                                return '🔴 Nonaktif (Maintenance Mode)';
                            }

                            $manualEnabled = $cfg['manual_enabled'] ?? true;
                            $channels = $cfg['channels'] ?? (isset($cfg['manual_bca']) ? $cfg : []);
                            $activeChannels = array_keys(array_filter($channels));

                            $manualStatus = $manualEnabled
                                ? (count($activeChannels) ? 'Manual: '.implode(', ', array_map(fn ($k) => strtoupper(str_replace('manual_', '', $k)), $activeChannels)) : 'Manual: Aktif (Tanpa Bank)')
                                : 'Manual: Nonaktif';

                            $gatewayEnabled = $cfg['gateway_enabled'] ?? false;
                            $gatewayProvider = strtoupper($cfg['gateway_provider'] ?? 'midtrans');
                            $gatewayMode = ucfirst($cfg['gateway_mode'] ?? 'sandbox');
                            $gatewayStatus = $gatewayEnabled ? "Gateway: {$gatewayProvider} ({$gatewayMode})" : 'Gateway: Nonaktif';

                            return "{$manualStatus} | {$gatewayStatus}";
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
                            $raw = is_array($record->value) ? $record->value : [];
                            $systemEnabled = $raw['system_enabled'] ?? true;
                            $manualEnabled = $raw['manual_enabled'] ?? true;
                            $channels = $raw['channels'] ?? (isset($raw['manual_bca']) ? $raw : []);
                            $activeChannels = array_keys(array_filter($channels));
                            $gatewayEnabled = $raw['gateway_enabled'] ?? false;
                            $gatewayProvider = $raw['gateway_provider'] ?? 'midtrans';
                            $gatewayMode = $raw['gateway_mode'] ?? 'sandbox';

                            return [
                                Toggle::make('system_enabled')
                                    ->label('Aktifkan Seluruh Sistem Pembayaran')
                                    ->helperText('Matikan toggle ini jika toko sedang dalam pemeliharaan (Maintenance Mode). Saat nonaktif, pembeli akan melihat soft warning bahwa pembayaran sedang dalam pemeliharaan.')
                                    ->default($systemEnabled)
                                    ->live(),

                                Fieldset::make('Mode Pembayaran Manual (Transfer Bank & Verifikasi Struk)')
                                    ->schema([
                                        Toggle::make('manual_enabled')
                                            ->label('Aktifkan Pembayaran Manual')
                                            ->helperText('Pembeli mentransfer dana ke rekening toko dan mengunggah struk transfer untuk diverifikasi admin.')
                                            ->default($manualEnabled)
                                            ->live(),

                                        CheckboxList::make('manual_channels')
                                            ->label('Pilihan Rekening Bank / QRIS yang Dibuka')
                                            ->options([
                                                'manual_bca' => 'Transfer Bank BCA',
                                                'manual_mandiri' => 'Transfer Bank Mandiri',
                                                'manual_bri' => 'Transfer Bank BRI',
                                                'manual_qris' => 'QRIS Manual (Statis)',
                                            ])
                                            ->default($activeChannels)
                                            ->visible(fn ($get) => (bool) $get('manual_enabled'))
                                            ->helperText('Hanya rekening yang sudah diisi nomor rekeningnya di Pengaturan yang akan tampil ke pembeli.'),
                                    ]),

                                Fieldset::make('Mode Pembayaran Otomatis (Payment Gateway)')
                                    ->schema([
                                        Toggle::make('gateway_enabled')
                                            ->label('Aktifkan Payment Gateway Otomatis')
                                            ->helperText('Pembeli membayar instan via Virtual Account / E-Wallet / QRIS Dinamis dan terverifikasi otomatis via Webhook callback.')
                                            ->default($gatewayEnabled)
                                            ->live(),

                                        Radio::make('gateway_provider')
                                            ->label('Penyedia Payment Gateway')
                                            ->options([
                                                'midtrans' => 'Midtrans (Snap & Core API)',
                                                'xendit' => 'Xendit (Invoice & VA)',
                                            ])
                                            ->default($gatewayProvider)
                                            ->visible(fn ($get) => (bool) $get('gateway_enabled')),

                                        Radio::make('gateway_mode')
                                            ->label('Environment Status')
                                            ->options([
                                                'sandbox' => 'Sandbox (Mode Testing & Percobaan)',
                                                'production' => 'Production (Mode Transaksi Nyata)',
                                            ])
                                            ->default($gatewayMode)
                                            ->visible(fn ($get) => (bool) $get('gateway_enabled')),
                                    ]),
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
                            $systemEnabled = (bool) ($data['system_enabled'] ?? true);
                            $manualEnabled = (bool) ($data['manual_enabled'] ?? false);
                            $selectedChannels = $data['manual_channels'] ?? [];
                            $allChannels = ['manual_bca', 'manual_mandiri', 'manual_bri', 'manual_qris'];
                            $channels = [];
                            foreach ($allChannels as $ch) {
                                $channels[$ch] = in_array($ch, $selectedChannels);
                            }

                            $gatewayEnabled = (bool) ($data['gateway_enabled'] ?? false);
                            $gatewayProvider = $data['gateway_provider'] ?? 'midtrans';
                            $gatewayMode = $data['gateway_mode'] ?? 'sandbox';

                            $val = [
                                'system_enabled' => $systemEnabled,
                                'manual_enabled' => $manualEnabled,
                                'channels' => $channels,
                                'gateway_enabled' => $gatewayEnabled,
                                'gateway_provider' => $gatewayProvider,
                                'gateway_mode' => $gatewayMode,
                            ];

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
