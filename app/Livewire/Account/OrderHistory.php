<?php

namespace App\Livewire\Account;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\ReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class OrderHistory extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $statusFilter = 'all';

    // State Modal Pembayaran & Transfer Manual
    public bool $showPaymentModal = false;

    public ?int $payingOrderId = null;

    public ?Order $payingOrder = null;

    public string $paymentMethod = 'manual_bca';

    public string $senderBank = '';

    public string $senderName = '';

    public string $paymentNotes = '';

    public $proofFile = null;

    public function mount()
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }
    }

    public function getActivePaymentMethods(): array
    {
        $settingMethods = Setting::get('payment_methods', []);
        if (! is_array($settingMethods)) {
            return [];
        }

        $available = [];

        $bcaAccount = trim((string) Setting::get('bank_bca_account', ''));
        if ($bcaAccount !== '') {
            $available['manual_bca'] = [
                'name' => 'Bank BCA',
                'account' => $bcaAccount,
                'holder' => (string) Setting::get('bank_bca_holder', ''),
            ];
        }

        $mandiriAccount = trim((string) Setting::get('bank_mandiri_account', ''));
        if ($mandiriAccount !== '') {
            $available['manual_mandiri'] = [
                'name' => 'Bank Mandiri',
                'account' => $mandiriAccount,
                'holder' => (string) Setting::get('bank_mandiri_holder', ''),
            ];
        }

        $briAccount = trim((string) Setting::get('bank_bri_account', ''));
        if ($briAccount !== '') {
            $available['manual_bri'] = [
                'name' => 'Bank BRI',
                'account' => $briAccount,
                'holder' => (string) Setting::get('bank_bri_holder', ''),
            ];
        }

        $qrisNmid = trim((string) Setting::get('qris_nmid', ''));
        if ($qrisNmid !== '') {
            $available['manual_qris'] = [
                'name' => 'QRIS (Gopay / OVO / Dana / BCA)',
                'account' => 'NMID: '.$qrisNmid,
                'holder' => (string) Setting::get('qris_holder', ''),
            ];
        }

        return array_filter($available, function ($key) use ($settingMethods) {
            return ! empty($settingMethods[$key]);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function openPaymentModal(int $orderId)
    {
        $order = Order::with(['items.product', 'items.unit', 'payments'])
            ->where('user_id', auth()->id())
            ->where('id', $orderId)
            ->where('status', OrderStatus::PENDING_PAYMENT)
            ->firstOrFail();

        $this->payingOrderId = $order->id;
        $this->payingOrder = $order;
        $this->senderName = auth()->user()->name ?? '';
        $this->senderBank = '';
        $this->paymentNotes = '';
        $this->proofFile = null;

        $activeMethods = $this->getActivePaymentMethods();
        $this->paymentMethod = ! empty($activeMethods) ? array_key_first($activeMethods) : '';
        $this->showPaymentModal = true;
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->payingOrderId = null;
        $this->payingOrder = null;
        $this->proofFile = null;
        $this->resetValidation();
    }

    public function submitPayment()
    {
        $activeMethods = array_keys($this->getActivePaymentMethods());
        if (empty($activeMethods)) {
            session()->flash('error', 'Saat ini kanal pembayaran belum tersedia atau sedang dalam pemeliharaan. Silakan hubungi admin toko.');

            return;
        }

        $inRule = implode(',', $activeMethods);

        $this->validate([
            'paymentMethod' => ['required', 'string', 'in:'.$inRule],
            'senderBank' => 'required|string|max:100',
            'senderName' => 'required|string|max:255',
            'proofFile' => 'required|image|mimes:jpg,jpeg,png,webp|max:3072', // Maksimal 3MB
            'paymentNotes' => 'nullable|string|max:500',
        ], [
            'paymentMethod.in' => 'Metode pembayaran tidak valid atau sedang dinonaktifkan.',
            'senderBank.required' => 'Nama bank asal pengirim wajib diisi.',
            'senderName.required' => 'Nama pemilik rekening pengirim wajib diisi.',
            'proofFile.required' => 'Foto bukti struk transfer wajib diunggah.',
            'proofFile.image' => 'Bukti transfer harus berupa gambar (JPG/PNG/WEBP).',
            'proofFile.mimes' => 'Format gambar yang diperbolehkan: JPG, JPEG, PNG, dan WEBP.',
            'proofFile.max' => 'Ukuran foto maksimal 3MB.',
        ]);

        $order = Order::where('user_id', auth()->id())
            ->where('id', $this->payingOrderId)
            ->whereIn('status', [OrderStatus::PENDING_PAYMENT, OrderStatus::MENUNGGU_VERIFIKASI])
            ->firstOrFail();

        // Simpan file bukti transfer ke storage privat (local disk)
        $storedPath = $this->proofFile->store('payment-proofs', 'local');

        try {
            DB::transaction(function () use ($order, $storedPath) {
                $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

                if (! in_array($lockedOrder->status, [OrderStatus::PENDING_PAYMENT, OrderStatus::MENUNGGU_VERIFIKASI])) {
                    throw new \RuntimeException('Status pesanan telah berubah dan tidak dapat menerima bukti pembayaran.');
                }

                // Buat entri pembayaran manual baru
                Payment::create([
                    'order_id' => $lockedOrder->id,
                    'method' => 'manual',
                    'provider' => $this->paymentMethod,
                    'amount' => $lockedOrder->total,
                    'status' => PaymentStatus::PENDING,
                    'proof_url' => $storedPath,
                    'payload' => [
                        'sender_bank' => $this->senderBank,
                        'sender_name' => $this->senderName,
                        'notes' => $this->paymentNotes,
                        'submitted_at' => now()->toIso8601String(),
                    ],
                ]);

                // Perbarui status pesanan menjadi MENUNGGU_VERIFIKASI
                $lockedOrder->update([
                    'status' => OrderStatus::MENUNGGU_VERIFIKASI,
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($storedPath);
            throw $e;
        }

        $this->showPaymentModal = false;
        session()->flash('success', "Bukti pembayaran pesanan #{$order->id} ({$order->invoice_no}) berhasil diunggah! Kasir/Admin akan segera memverifikasi pembayaran Anda.");
    }

    public function filterByStatus(string $status)
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function cancelOrder(int $orderId)
    {
        DB::transaction(function () use ($orderId) {
            $order = Order::where('user_id', auth()->id())
                ->where('id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($order->status, [OrderStatus::MENUNGGU_ONGKIR, OrderStatus::PENDING_PAYMENT])) {
                throw new \RuntimeException('Pesanan dengan status ini tidak dapat dibatalkan.');
            }

            // Batalkan order & lepaskan reservasi
            $reservationService = app(ReservationService::class);
            $reservationService->release($order->id);

            $order->update([
                'status' => OrderStatus::CANCELLED,
                'note' => trim(($order->note ? $order->note.' | ' : '').'Dibatalkan oleh pembeli pada '.now()->format('d/m/Y H:i')),
            ]);
        });

        session()->flash('success', "Pesanan #{$orderId} berhasil dibatalkan dan stok telah dilepaskan.");
    }

    public function render()
    {
        $query = Order::with(['items.product', 'items.unit', 'payments'])
            ->where('user_id', auth()->id())
            ->latest();

        if ($this->statusFilter === 'pending') {
            $query->whereIn('status', [OrderStatus::MENUNGGU_ONGKIR, OrderStatus::PENDING_PAYMENT, OrderStatus::MENUNGGU_VERIFIKASI]);
        } elseif ($this->statusFilter === 'paid') {
            $query->whereIn('status', [OrderStatus::PAID, OrderStatus::SHIPPED, OrderStatus::COMPLETED]);
        } elseif ($this->statusFilter === 'cancelled') {
            $query->whereIn('status', [OrderStatus::CANCELLED, OrderStatus::EXPIRED, OrderStatus::REJECTED]);
        }

        $orders = $query->paginate(10);

        return view('livewire.account.order-history', [
            'orders' => $orders,
        ]);
    }
}
