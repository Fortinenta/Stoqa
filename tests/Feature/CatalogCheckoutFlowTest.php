<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ReservationStatus;
use App\Enums\StockMovementType;
use App\Livewire\Checkout\CheckoutView;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\StockLevel;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CartService;
use App\Services\ReservationService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Warehouse $warehouse;

    protected User $customer;

    protected Product $product;

    protected ProductUnit $unitPcs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::create([
            'name' => 'Gudang Utama',
            'code' => 'WH-UTAMA',
            'is_default' => true,
        ]);

        $category = Category::create([
            'name' => 'Sembako',
            'slug' => 'sembako',
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Minyak Goreng 1L',
            'sku' => 'MYK-001',
            'base_unit' => 'Pcs',
            'is_active' => true,
        ]);

        $this->unitPcs = ProductUnit::create([
            'product_id' => $this->product->id,
            'unit_name' => 'Pcs',
            'conversion_to_base' => 1,
        ]);

        ProductPrice::create([
            'product_id' => $this->product->id,
            'unit_id' => $this->unitPcs->id,
            'min_qty' => 1,
            'price' => 15000,
            'tier_name' => 'Ecer',
        ]);

        $this->customer = User::create([
            'name' => 'Budi Pembeli',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'password' => bcrypt('secret'),
        ]);

        // Inbound stok awal sebanyak 50 Pcs @ 12.000
        $stockService = app(StockService::class);
        $stockService->receive([
            'warehouse_id' => $this->warehouse->id,
            'received_date' => now()->toDateString(),
            'shipping_cost' => 0,
            'other_cost' => 0,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty_base' => 50,
                    'unit_price' => 12000,
                ],
            ],
        ]);
    }

    public function test_complete_checkout_to_payment_fulfillment_flow()
    {
        $this->actingAs($this->customer);

        // 1. Tambah produk ke keranjang
        $cartService = app(CartService::class);
        $cartService->add(
            productId: $this->product->id,
            units: [$this->unitPcs->id => 10]
        );

        $this->assertEquals(1, $cartService->count());
        $cartData = $cartService->get($this->customer);
        $this->assertEquals(150000, $cartData['subtotal']); // 10 * 15.000

        // 2. Checkout via Livewire component
        Livewire::test(CheckoutView::class)
            ->set('customer_name', 'Budi Santoso')
            ->set('phone', '081234567890')
            ->set('shipping_address', 'Jl. Merdeka No. 45 Jakarta')
            ->set('note', 'Tolong packing rapi')
            ->call('placeOrder')
            ->assertRedirect(url('/pesanan-saya'));

        // Cek order di database
        $order = Order::where('user_id', $this->customer->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(OrderStatus::MENUNGGU_ONGKIR, $order->status);
        $this->assertEquals(150000, $order->subtotal);

        // Cek stok ter-hold
        $stockLevel = StockLevel::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertEquals(50, $stockLevel->on_hand);
        $this->assertEquals(10, $stockLevel->reserved);
        $this->assertEquals(40, $stockLevel->available);

        // Cek reservasi aktif
        $reservation = DB::table('stock_reservations')
            ->where('order_id', $order->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertNotNull($reservation);
        $this->assertEquals(ReservationStatus::ACTIVE->value, $reservation->status);
        $this->assertEquals(10, $reservation->qty_base);

        // 3. Admin gudang menginput ongkir
        $order->update([
            'shipping_cost' => 25000,
            'total' => $order->subtotal + 25000,
            'status' => OrderStatus::PENDING_PAYMENT,
            'invoice_number' => 'INV/'.now()->format('Y/m').'/0001',
            'payment_due_at' => now()->addHours(12),
        ]);

        // Perpanjang durasi hold reservasi ke 12 jam
        DB::table('stock_reservations')
            ->where('order_id', $order->id)
            ->update(['expires_at' => now()->addHours(12)]);

        $this->assertEquals(175000, $order->fresh()->total);

        // 4. Pembayaran berhasil dikonfirmasi -> Potong Stok FIFO
        $reservationService = app(ReservationService::class);
        $reservationService->consume($order);

        $order->update([
            'status' => OrderStatus::PAID,
            'payment_status' => 'paid',
        ]);

        // Verifikasi kondisi akhir stok
        $stockLevel->refresh();
        $this->assertEquals(40, $stockLevel->on_hand);
        $this->assertEquals(0, $stockLevel->reserved);
        $this->assertEquals(40, $stockLevel->available);

        // Cek status reservasi menjadi CONSUMED
        $reservationStatus = DB::table('stock_reservations')
            ->where('order_id', $order->id)
            ->value('status');
        $this->assertEquals(ReservationStatus::CONSUMED->value, $reservationStatus);

        // Cek ledger mutasi barang keluar
        $outMovement = DB::table('stock_movements')
            ->where('ref_type', 'order')
            ->where('ref_id', $order->id)
            ->first();

        $this->assertNotNull($outMovement);
        $this->assertEquals(StockMovementType::OUT->value, $outMovement->type);
        $this->assertEquals(-10, $outMovement->qty_base);
    }
}
