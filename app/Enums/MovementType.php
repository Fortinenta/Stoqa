<?php

namespace App\Enums;

enum MovementType: string
{
    case In = 'IN';
    case Out = 'OUT';
    case Adjust = 'ADJUST';
}
