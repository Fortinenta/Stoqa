<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use App\Enums\StockMovementType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StockMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->required(),
                Select::make('type')
                    ->options(StockMovementType::class)
                    ->required(),
                TextInput::make('qty_base')
                    ->required()
                    ->numeric(),
                TextInput::make('ref_type'),
                TextInput::make('ref_id')
                    ->numeric(),
                TextInput::make('note'),
                TextInput::make('created_by')
                    ->numeric(),
            ]);
    }
}
