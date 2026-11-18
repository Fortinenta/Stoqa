<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'unit_id',
        'min_qty',
        'price',
        'valid_from',
    ];

    protected function casts(): array
    {
        return [
            'min_qty' => 'integer',
            'price' => 'decimal:2',
            'valid_from' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }
}
