<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'inbound_item_id',
        'qty_initial',
        'qty_remaining',
        'unit_cost_landed',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'qty_initial' => 'integer',
            'qty_remaining' => 'integer',
            'unit_cost_landed' => 'decimal:4',
            'received_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inboundItem(): BelongsTo
    {
        return $this->belongsTo(InboundItem::class);
    }

    public function orderItemBatches(): HasMany
    {
        return $this->hasMany(OrderItemBatch::class);
    }
}
