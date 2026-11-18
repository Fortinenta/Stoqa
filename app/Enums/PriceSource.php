<?php

namespace App\Enums;

enum PriceSource: string
{
    case Tier = 'tier';
    case Customer = 'customer';
    case Manual = 'manual';
}
