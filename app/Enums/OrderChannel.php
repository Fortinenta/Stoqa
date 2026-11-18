<?php

namespace App\Enums;

enum OrderChannel: string
{
    case Catalog = 'catalog';
    case Manual = 'manual';
}
