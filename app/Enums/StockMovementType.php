<?php

namespace App\Enums;

enum StockMovementType: string
{
    case IN = 'IN';
    case OUT = 'OUT';
    case ADJUST = 'ADJUST';

    public function label(): string
    {
        return match ($this) {
            self::IN => 'Barang Masuk',
            self::OUT => 'Barang Keluar',
            self::ADJUST => 'Penyesuaian Opname',
        };
    }
}
