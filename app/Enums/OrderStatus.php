<?php

namespace App\Enums;

enum OrderStatus: string
{
    case MENUNGGU_ONGKIR = 'MENUNGGU_ONGKIR';
    case PENDING_PAYMENT = 'PENDING_PAYMENT';
    case PAID = 'PAID';
    case SHIPPED = 'SHIPPED';
    case COMPLETED = 'COMPLETED';
    case REJECTED = 'REJECTED';
    case CANCELLED = 'CANCELLED';
    case EXPIRED = 'EXPIRED';

    public function label(): string
    {
        return match ($this) {
            self::MENUNGGU_ONGKIR => 'Menunggu Ongkir',
            self::PENDING_PAYMENT => 'Menunggu Pembayaran',
            self::PAID => 'Sudah Dibayar',
            self::SHIPPED => 'Sedang Dikirim',
            self::COMPLETED => 'Selesai',
            self::REJECTED => 'Ditolak',
            self::CANCELLED => 'Dibatalkan',
            self::EXPIRED => 'Kadaluarsa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MENUNGGU_ONGKIR => 'warning',
            self::PENDING_PAYMENT, self::SHIPPED => 'info',
            self::PAID, self::COMPLETED => 'success',
            self::REJECTED, self::CANCELLED, self::EXPIRED => 'danger',
        };
    }
}
