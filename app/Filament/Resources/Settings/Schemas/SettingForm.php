<?php

namespace App\Filament\Resources\Settings\Schemas;

use App\Models\Setting;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Kunci Konfigurasi')
                    ->disabled()
                    ->required(),
                TextInput::make('value')
                    ->label('Nilai Pengaturan')
                    ->rules(function (?Setting $record) {
                        $key = $record?->key;
                        if (in_array($key, ['shipping_due_hours', 'payment_hold_hours'])) {
                            return ['required', 'integer', 'min:1', 'max:168'];
                        }
                        if ($key === 'help_whatsapp') {
                            return ['required', 'string', 'regex:/^[0-9+]{8,20}$/'];
                        }
                        if ($key === 'payment_methods') {
                            return ['required', 'json'];
                        }

                        return ['required', 'string', 'max:255'];
                    })
                    ->helperText(fn (?Setting $record) => match ($record?->key) {
                        'shipping_due_hours', 'payment_hold_hours' => 'Nilai bilangan bulat dalam satuan jam (antara 1 hingga 168 jam / 7 hari).',
                        'help_whatsapp' => 'Nomor WhatsApp resmi diawali kode negara (contoh: 6281234567890).',
                        'payment_methods' => 'Format JSON konfigurasi kanal pembayaran aktif.',
                        default => null,
                    })
                    ->required(),
            ]);
    }
}
