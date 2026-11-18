<?php

namespace App\Filament\Resources\InboundOrders\Pages;

use App\Filament\Resources\InboundOrders\InboundOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInboundOrders extends ListRecords
{
    protected static string $resource = InboundOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
