<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case ACTIVE = 'ACTIVE';
    case CONSUMED = 'CONSUMED';
    case RELEASED = 'RELEASED';
    case EXPIRED = 'EXPIRED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Aktif (Hold)',
            self::CONSUMED => 'Terpakai (Fulfillment)',
            self::RELEASED => 'Dilepas',
            self::EXPIRED => 'Kadaluarsa',
        };
    }
}
