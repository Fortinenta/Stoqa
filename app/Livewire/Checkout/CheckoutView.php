<?php

namespace App\Livewire\Checkout;

use App\Enums\OrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Services\CartService;
use App\Services\ReservationService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CheckoutView extends Component
{
    public string $customer_name = '';

    public string $phone = '';

    public string $shipping_address = '';

    public string $note = '';

    public function mount(CartService $cartService)
    {
        // 1. Cek isi keranjang
        if ($cartService->count() === 0) {
            session()->flash('error', 'Keranjang Anda masih kosong.');
            $this->redirect(url('/'));

            return;
        }

        // 2. Wajib Login
        if (! auth()->check()) {
            session()->put('url.intended', url('/checkout'));
            $this->redirect(route('login'));

            return;
        }

        $user = auth()->user();
        $this->customer_name = $user->name;
        $this->phone = $user->phone ?? '';
        $this->shipping_address = $user->address ?? '';
    }

    public function placeOrder(CartService $cartService, ReservationService $reservationService)
    {
        $this->validate([
            'customer_name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'shipping_address' => 'required|string|max:1000',
            'note' => 'nullable|string|max:500',
        ], [
            'customer_name.required' => 'Nama penerima wajib diisi.',
            'phone.required' => 'Nomor telepon / WhatsApp wajib diisi.',
            'shipping_address.required' => 'Alamat pengiriman lengkap wajib diisi.',
        ]);

        $user = auth()->user();
        $user->update([
            'phone' => $this->phone,
            'address' => $this->shipping_address,
        ]);

        $cart = $cartService->get($user);
        if (empty($cart['items'])) {
            session()->flash('error', 'Keranjang Anda kosong.');
            $this->redirect(url('/'));

            return;
        }

        // Ambil batas durasi pengisian ongkir admin (default 3 jam)
        $shippingDueHours = (int) (Setting::get('shipping_due_hours', 3));
        $shippingDueAt = now()->addHours($shippingDueHours);
        $defaultWarehouseId = Warehouse::where('is_default', true)->value('id') ?? 1;

        try {
            $order = DB::transaction(function () use ($cart, $shippingDueAt, $reservationService, $user, $defaultWarehouseId) {
                // 1. Buat Header Order dengan snapshot kontak dan alamat pengiriman
                $order = Order::create([
                    'user_id' => $user->id,
                    'warehouse_id' => $defaultWarehouseId,
                    'channel' => 'catalog',
                    'customer_name' => $this->customer_name,
                    'shipping_recipient_name' => $this->customer_name,
                    'shipping_phone' => $this->phone,
                    'shipping_address' => $this->shipping_address,
                    'status' => OrderStatus::MENUNGGU_ONGKIR,
                    'subtotal' => $cart['subtotal'],
                    'shipping_cost' => 0,
                    'other_cost' => 0,
                    'total' => $cart['subtotal'],
                    'payment_status' => 'unpaid',
                    'shipping_due_at' => $shippingDueAt,
                    'note' => $this->note,
                ]);

                // 2. Buat Items & Siapkan Kebutuhan Reservasi
                $reservationItems = [];

                foreach ($cart['items'] as $item) {
                    $breakdowns = ! empty($item['units_breakdown']) ? $item['units_breakdown'] : [
                        [
                            'unit_id' => null,
                            'qty' => $item['total_base_qty'],
                            'qty_base' => $item['total_base_qty'],
                            'conversion' => 1,
                        ],
                    ];

                    // Buat OrderItem per pecahan unit agar qty × unit_conversion × unit_price = line_total
                    foreach ($breakdowns as $b) {
                        $lineBaseQty = (int) $b['qty_base'];
                        $lineTotal = round($lineBaseQty * (float) $item['unit_price'], 0);

                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $item['product_id'],
                            'unit_id' => $b['unit_id'],
                            'qty' => $b['qty'],
                            'qty_base' => $lineBaseQty,
                            'unit_price' => $item['unit_price'],
                            'price_source' => $item['price_source'],
                            'line_total' => $lineTotal,
                        ]);
                    }

                    $reservationItems[] = [
                        'product_id' => $item['product_id'],
                        'qty_base' => $item['total_base_qty'],
                    ];
                }

                // 3. Tahan (Hold) Stok secara atomik dengan row-locking
                $reservationService->reserve(
                    orderId: $order->id,
                    warehouseId: $order->warehouse_id,
                    items: $reservationItems,
                    expiresAt: $shippingDueAt
                );

                return $order;
            });

            // Kosongkan keranjang
            $cartService->clear();

            session()->flash('success', "Pesanan #{$order->id} berhasil dibuat! Stok barang telah ditahan. Admin akan mengisi ongkir dalam waktu maks. {$shippingDueHours} jam.");
            $this->redirect(url('/pesanan-saya'));
        } catch (InsufficientStockException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            session()->flash('error', 'Terjadi kesalahan sistem saat memproses pesanan: '.$e->getMessage());
        }
    }

    public function render(CartService $cartService)
    {
        $cart = $cartService->get(auth()->user());

        return view('livewire.checkout.checkout-view', [
            'cart' => $cart,
        ]);
    }
}
