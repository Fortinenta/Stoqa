<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\StockLevel;
use App\Models\Warehouse;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $product = $this->record;

        // 1. Buat Satuan Dasar otomatis jika belum ada di relasi units
        if (! empty($product->base_unit)) {
            $product->units()->firstOrCreate(
                ['unit_name' => $product->base_unit],
                ['conversion_to_base' => 1]
            );
        }

        // 2. Buat inisialisasi Stock Level di semua gudang aktif
        $warehouses = Warehouse::all();
        foreach ($warehouses as $wh) {
            StockLevel::firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $wh->id],
                ['on_hand' => 0, 'reserved' => 0]
            );
        }
    }
}
