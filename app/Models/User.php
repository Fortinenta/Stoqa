<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'business_name',
        'address',
        'tax_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Admin & staf gudang bisa akses panel admin Filament
        return $this->is_active && ($this->hasAnyRole(['admin', 'staf_gudang']) || $this->email === 'admin@example.com');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function customerPrices(): HasMany
    {
        return $this->hasMany(CustomerPrice::class);
    }
}
