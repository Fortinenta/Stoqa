<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'type',
        'qty_base',
        'ref_type',
        'ref_id',
        'note',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'qty_base' => 'integer',
            'created_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
