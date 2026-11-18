<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InboundItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inbound_order_id',
        'product_id',
        'qty_base',
        'unit_price',
        'allocated_cost',
    ];

    protected function casts(): array
    {
        return [
            'qty_base' => 'integer',
            'unit_price' => 'decimal:2',
            'allocated_cost' => 'decimal:2',
        ];
    }

    public function inboundOrder(): BelongsTo
    {
        return $this->belongsTo(InboundOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): HasOne
    {
        return $this->hasOne(StockBatch::class);
    }
}
