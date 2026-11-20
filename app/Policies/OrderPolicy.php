<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Tentukan apakah user dapat melihat daftar pesanan.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'staf_gudang']);
    }

    /**
     * Tentukan apakah user dapat melihat detail pesanan.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->hasRole(['admin', 'staf_gudang'])) {
            return true;
        }

        return (int) $user->id === (int) $order->user_id;
    }

    /**
     * Tentukan apakah user dapat membuat pesanan manual di panel.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'staf_gudang']);
    }

    /**
     * Pesanan historis tidak boleh diubah langsung tanpa alur bisnis resmi.
     */
    public function update(User $user, Order $order): bool
    {
        return false;
    }

    /**
     * Pesanan tidak boleh dihapus demi integritas jejak audit keuangan.
     */
    public function delete(User $user, Order $order): bool
    {
        return false;
    }

    /**
     * Hak akses khusus untuk melihat HPP (COGS) dan Laba Kotor.
     * Hanya admin yang berhak; staf gudang dilarang melihat angka margin keuntungan.
     */
    public function viewFinancials(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
