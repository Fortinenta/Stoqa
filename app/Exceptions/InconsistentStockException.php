<?php

namespace App\Exceptions;

use Exception;

class InconsistentStockException extends Exception
{
    public function __construct(public int $productId, string $message = '')
    {
        if (empty($message)) {
            $message = "Terjadi inkonsistensi data: stok fisik di stock_levels mencukupi tetapi saldo batch stock_batches tidak mencukupi untuk produk ID {$productId}.";
        }
        parent::__construct($message);
    }
}
