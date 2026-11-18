<?php

namespace App\Filament\Resources\InboundOrders\Pages;

use App\Filament\Resources\InboundOrders\InboundOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInboundOrder extends EditRecord
{
    protected static string $resource = InboundOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
