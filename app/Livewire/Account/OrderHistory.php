<?php

namespace App\Livewire\Account;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\ReservationService;
use Illuminate\Support\Facades\DB;
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
        $this->paymentMethod = 'manual_bca';
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
        $this->validate([
            'paymentMethod' => ['required', 'string', 'in:manual_bca,manual_mandiri,manual_bri,manual_qris'],
            'senderBank' => 'required|string|max:100',
            'senderName' => 'required|string|max:255',
            'proofFile' => 'required|image|max:3072', // Maksimal 3MB
            'paymentNotes' => 'nullable|string|max:500',
        ], [
            'paymentMethod.in' => 'Metode pembayaran tidak valid.',
            'senderBank.required' => 'Nama bank asal pengirim wajib diisi.',
            'senderName.required' => 'Nama pemilik rekening pengirim wajib diisi.',
            'proofFile.required' => 'Foto bukti struk transfer wajib diunggah.',
            'proofFile.image' => 'Bukti transfer harus berupa gambar (JPG/PNG).',
            'proofFile.max' => 'Ukuran foto maksimal 3MB.',
        ]);

        $order = Order::where('user_id', auth()->id())
            ->where('id', $this->payingOrderId)
            ->whereIn('status', [OrderStatus::PENDING_PAYMENT, OrderStatus::MENUNGGU_VERIFIKASI])
            ->firstOrFail();

        // Simpan file bukti transfer ke storage privat (local disk)
        $storedPath = $this->proofFile->store('payment-proofs', 'local');

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
