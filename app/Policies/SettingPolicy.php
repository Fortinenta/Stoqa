<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    /**
     * Hanya admin yang boleh mengelola pengaturan sistem.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return false; // Parameter sistem sudah terdefinisi
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Setting $setting): bool
    {
        return false; // Parameter sistem tidak boleh dihapus
    }
}
