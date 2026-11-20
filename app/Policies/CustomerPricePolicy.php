<?php

namespace App\Policies;

use App\Models\CustomerPrice;
use App\Models\User;

class CustomerPricePolicy
{
    /**
     * Hanya admin yang boleh mengelola atau melihat harga khusus pelanggan.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, CustomerPrice $customerPrice): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, CustomerPrice $customerPrice): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, CustomerPrice $customerPrice): bool
    {
        return $user->hasRole('admin');
    }
}
