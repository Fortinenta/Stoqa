<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(public int $productId, public int $requested = 0, public int $available = 0, string $message = '')
    {
        if (empty($message)) {
            $message = "Stok produk ID {$productId} tidak mencukupi (diminta: {$requested}, tersedia: {$available}).";
        }
        parent::__construct($message);
    }
}
