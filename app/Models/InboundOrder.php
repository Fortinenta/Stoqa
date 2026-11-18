<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InboundOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'warehouse_id',
        'received_date',
        'shipping_cost',
        'other_cost',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'shipping_cost' => 'decimal:2',
            'other_cost' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InboundItem::class);
    }
}
