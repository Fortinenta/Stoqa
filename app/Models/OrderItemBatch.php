<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'stock_batch_id',
        'qty_base',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'qty_base' => 'integer',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function stockBatch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class);
    }
}
