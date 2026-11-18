<?php

namespace App\Livewire\Account;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\ReservationService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class OrderHistory extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';

    public function mount()
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }
    }

    public function filterByStatus(string $status)
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function cancelOrder(int $orderId)
    {
        $order = Order::where('user_id', auth()->id())
            ->where('id', $orderId)
            ->whereIn('status', [OrderStatus::MENUNGGU_ONGKIR, OrderStatus::PENDING_PAYMENT])
            ->firstOrFail();

        // Batalkan order & lepaskan reservasi
        $reservationService = app(ReservationService::class);
        $reservationService->release($order->id);

        $order->update([
            'status' => OrderStatus::CANCELLED,
            'note' => trim(($order->note ? $order->note.' | ' : '').'Dibatalkan oleh pembeli pada '.now()->format('d/m/Y H:i')),
        ]);

        session()->flash('success', "Pesanan #{$order->id} berhasil dibatalkan dan stok telah dilepaskan.");
    }

    public function render()
    {
        $query = Order::with(['items.product', 'items.unit'])
            ->where('user_id', auth()->id())
            ->latest();

        if ($this->statusFilter === 'pending') {
            $query->whereIn('status', [OrderStatus::MENUNGGU_ONGKIR, OrderStatus::PENDING_PAYMENT]);
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
