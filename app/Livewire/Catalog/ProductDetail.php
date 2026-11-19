<?php

namespace App\Livewire\Catalog;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Services\CartService;
use App\Services\PricingCalculator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProductDetail extends Component
{
    public Product $product;

    // Array jumlah yang dipilih per unit_id: [unit_id => qty]
    public array $quantities = [];

    // Hasil perhitungan kalkulator dinamis
    public int $totalBaseQty = 0;

    public float $subtotal = 0.0;

    public float $savings = 0.0;

    public ?string $appliedTier = null;

    public int $availableStock = 0;

    public function mount(string $sku, PricingCalculator $calculator)
    {
        $this->product = Product::with(['category', 'units', 'prices.unit', 'customerPrices.unit', 'stockLevels'])
            ->where('sku', $sku)
            ->firstOrFail();

        $this->availableStock = max(0, $this->product->stockLevels->sum('on_hand') - $this->product->stockLevels->sum('reserved'));

        // Inisialisasi kuantitas default untuk setiap satuan yang dimiliki produk
        foreach ($this->product->units as $unit) {
            $this->quantities[$unit->id] = 0;
        }

        // Jika produk belum memiliki units di product_units, buat default 1 unit dasar
        if (empty($this->quantities)) {
            $defaultUnit = ProductUnit::firstOrCreate(
                ['product_id' => $this->product->id, 'unit_name' => $this->product->base_unit],
                ['conversion_to_base' => 1]
            );
            $this->quantities[$defaultUnit->id] = 0;
        }

        $this->calculate($calculator);
    }

    public function updatedQuantities(): void
    {
        $calculator = app(PricingCalculator::class);
        $this->calculate($calculator);
    }

    protected function calculate(PricingCalculator $calculator): void
    {
        $this->totalBaseQty = 0;

        foreach ($this->quantities as $unitId => $qty) {
            $qty = max(0, (int) $qty);
            $this->quantities[$unitId] = $qty;

            $unit = ProductUnit::find($unitId);
            $conversion = $unit ? $unit->conversion_to_base : 1;
            $this->totalBaseQty += ($qty * $conversion);
        }

        if ($this->totalBaseQty <= 0) {
            $this->subtotal = 0.0;
            $this->savings = 0.0;
            $this->appliedTier = null;

            return;
        }

        $user = auth()->user();
        $pricing = $calculator->calculateBestPrice($this->product, $this->totalBaseQty, $user);

        $this->subtotal = $pricing['total_price'];
        $this->appliedTier = $pricing['applied_tier'];

        // Hitung estimasi harga jika dibeli ecer satuan dasar
        $basePriceRecord = $this->product->prices->where('unit.conversion_to_base', 1)->first()
            ?? $this->product->prices->first();
        $baseUnitPrice = $basePriceRecord ? (float) $basePriceRecord->price : 0.0;
        $standardCost = $this->totalBaseQty * $baseUnitPrice;

        $this->savings = max(0, $standardCost - $this->subtotal);
    }

    public function addToCart(CartService $cartService)
    {
        if ($this->totalBaseQty <= 0) {
            session()->flash('error', 'Silakan tentukan kuantitas barang terlebih dahulu.');

            return;
        }

        if ($this->totalBaseQty > $this->availableStock) {
            session()->flash('error', "Jumlah pesanan ({$this->totalBaseQty} {$this->product->base_unit}) melebihi stok yang tersedia ({$this->availableStock} {$this->product->base_unit}).");

            return;
        }

        $validUnits = array_filter($this->quantities, fn ($q) => $q > 0);
        $cartService->add($this->product->id, $validUnits);

        session()->flash('success', "{$this->product->name} berhasil ditambahkan ke keranjang. Silakan pilih produk lainnya atau buka menu keranjang untuk checkout.");
        $this->redirect(url('/'), navigate: true);
    }

    public function render()
    {
        $canSeePrice = $this->product->show_price_public || auth()->check();

        return view('livewire.catalog.product-detail', [
            'canSeePrice' => $canSeePrice,
        ]);
    }
}
