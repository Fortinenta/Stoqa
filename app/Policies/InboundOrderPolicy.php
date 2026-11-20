<?php

namespace App\Policies;

use App\Models\InboundOrder;
use App\Models\User;

class InboundOrderPolicy
{
    /**
     * Admin dan staf gudang dapat melihat daftar barang masuk.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'staf_gudang']);
    }

    public function view(User $user, InboundOrder $inboundOrder): bool
    {
        return $user->hasRole(['admin', 'staf_gudang']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'staf_gudang']);
    }

    /**
     * Barang masuk yang sudah dicatat tidak boleh diedit atau dihapus secara sepihak.
     */
    public function update(User $user, InboundOrder $inboundOrder): bool
    {
        return false;
    }

    public function delete(User $user, InboundOrder $inboundOrder): bool
    {
        return false;
    }
}
