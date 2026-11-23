<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentProofController extends Controller
{
    /**
     * Tampilkan atau unduh bukti pembayaran secara aman.
     * Hanya dapat diakses oleh admin, staf gudang, atau pemilik order terkait.
     */
    public function show(Request $request, Order $order, Payment $payment)
    {
        if ($payment->order_id !== $order->id) {
            abort(404, 'Pembayaran tidak sesuai dengan data pesanan.');
        }

        $user = $request->user();
        if (! $user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        $isOwner = (int) $user->id === (int) $order->user_id;
        $isStaff = $user->hasRole(['admin', 'staf_gudang']);

        if (! $isOwner && ! $isStaff) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat bukti transfer ini.');
        }

        $path = $payment->proof_url;
        if (! $path) {
            abort(404, 'Bukti pembayaran belum diunggah.');
        }

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
        ];

        // Cari di disk local (private), fallback ke disk public untuk kompatibilitas data lama
        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->response($path, headers: $headers);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->response($path, headers: $headers);
        }

        abort(404, 'File bukti transfer fisik tidak ditemukan di server.');
    }
}
