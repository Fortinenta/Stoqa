<?php

namespace App\Filament\Resources\InboundOrders\Pages;

use App\Filament\Resources\InboundOrders\InboundOrderResource;
use App\Services\StockService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInboundOrder extends CreateRecord
{
    protected static string $resource = InboundOrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $stockService = app(StockService::class);

        return $stockService->receiveInbound($data, $items, auth()->id());
    }
}
