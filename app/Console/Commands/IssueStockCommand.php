<?php

namespace App\Console\Commands;

use App\Exceptions\InsufficientStockException;
use App\Services\StockService;
use Illuminate\Console\Command;

class IssueStockCommand extends Command
{
    protected $signature = 'stock:issue {productId} {warehouseId} {qty}';

    protected $description = 'Keluarkan stok untuk keperluan pengujian';

    public function handle(StockService $stockService): int
    {
        $productId = (int) $this->argument('productId');
        $warehouseId = (int) $this->argument('warehouseId');
        $qty = (int) $this->argument('qty');

        try {
            $result = $stockService->issue($productId, $warehouseId, $qty);
            $this->info('SUCCESS: COGS '.$result['cogs']);

            return Command::SUCCESS;
        } catch (InsufficientStockException $e) {
            $this->error('INSUFFICIENT: '.$e->getMessage());

            return 2;
        } catch (\Throwable $e) {
            $this->error('ERROR: '.$e->getMessage());

            return 1;
        }
    }
}
