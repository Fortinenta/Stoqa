<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'channel',
        'customer_name',
        'warehouse_id',
        'status',
        'subtotal',
        'shipping_cost',
        'other_cost',
        'total',
        'cogs_total',
        'payment_status',
        'invoice_no',
        'shipping_due_at',
        'invoiced_at',
        'expires_at',
        'paid_at',
        'rejected_at',
        'reject_reason',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'other_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'cogs_total' => 'decimal:2',
            'shipping_due_at' => 'datetime',
            'invoiced_at' => 'datetime',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function costs(): HasMany
    {
        return $this->hasMany(OrderCost::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
