<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'unit_id',
        'qty',
        'qty_base',
        'unit_price',
        'price_source',
        'line_total',
        'cogs_total',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'qty_base' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'cogs_total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }

    public function batchAllocations(): HasMany
    {
        return $this->hasMany(OrderItemBatch::class);
    }
}
