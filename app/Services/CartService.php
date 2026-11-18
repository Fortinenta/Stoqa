<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected const SESSION_KEY = 'mysto_cart';

    public function __construct(protected PricingCalculator $pricingCalculator) {}

    /**
     * Ambil seluruh item keranjang beserta perhitungan kalkulator harga terkini.
     */
    public function get(?User $user = null): array
    {
        $rawCart = Session::get(self::SESSION_KEY, []);
        $items = [];
        $totalBaseQtyAll = 0;
        $totalPriceAll = 0.0;
        $totalSavingsAll = 0.0;

        foreach ($rawCart as $key => $cartRow) {
            $product = Product::with(['category', 'units', 'prices', 'stockLevels'])->find($cartRow['product_id']);
            if (! $product) {
                continue;
            }

            $totalBaseQty = 0;
            $unitsBreakdown = [];

            foreach ($cartRow['units'] as $unitId => $qty) {
                $qty = (int) $qty;
                if ($qty <= 0) {
                    continue;
                }

                $unit = ProductUnit::find($unitId);
                $conversion = $unit ? $unit->conversion_to_base : 1;
                $unitBase = $qty * $conversion;
                $totalBaseQty += $unitBase;

                $unitsBreakdown[] = [
                    'unit_id' => $unitId,
                    'unit_name' => $unit ? $unit->unit_name : $product->base_unit,
                    'qty' => $qty,
                    'conversion' => $conversion,
                    'qty_base' => $unitBase,
                ];
            }

            if ($totalBaseQty <= 0) {
                continue;
            }

            // Hitung harga terbaik lewat PricingCalculator
            $pricing = $this->pricingCalculator->calculateBestPrice($product, $totalBaseQty, $user);

            // Hitung estimasi harga jika dibeli ecer standar tanpa tier
            $basePriceRecord = $product->prices->where('unit.conversion_to_base', 1)->first()
                ?? $product->prices->first();
            $baseUnitPrice = $basePriceRecord ? (float) $basePriceRecord->price : 0.0;
            $standardCost = $totalBaseQty * $baseUnitPrice;
            $savings = max(0, $standardCost - $pricing['total_price']);

            $availableStock = max(0, $product->stockLevels->sum('on_hand') - $product->stockLevels->sum('reserved'));

            $items[$key] = [
                'key' => $key,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'base_unit' => $product->base_unit,
                'available_stock' => $availableStock,
                'units' => $cartRow['units'],
                'units_breakdown' => $unitsBreakdown,
                'total_base_qty' => $totalBaseQty,
                'unit_price' => $pricing['unit_price'],
                'total_price' => $pricing['total_price'],
                'price_source' => $pricing['price_source'],
                'applied_tier' => $pricing['applied_tier'],
                'savings' => $savings,
            ];

            $totalBaseQtyAll += $totalBaseQty;
            $totalPriceAll += $pricing['total_price'];
            $totalSavingsAll += $savings;
        }

        return [
            'items' => $items,
            'total_items' => count($items),
            'total_base_qty' => $totalBaseQtyAll,
            'subtotal' => round($totalPriceAll, 2),
            'total_savings' => round($totalSavingsAll, 2),
        ];
    }

    /**
     * Tambah produk ke keranjang.
     *
     * @param  array  $units  [unit_id => qty, ...]
     */
    public function add(int $productId, array $units): void
    {
        $cart = Session::get(self::SESSION_KEY, []);
        $key = 'prod_'.$productId;

        if (! isset($cart[$key])) {
            $cart[$key] = [
                'product_id' => $productId,
                'units' => [],
            ];
        }

        foreach ($units as $unitId => $qty) {
            $current = $cart[$key]['units'][$unitId] ?? 0;
            $cart[$key]['units'][$unitId] = $current + (int) $qty;
        }

        Session::put(self::SESSION_KEY, $cart);
    }

    /**
     * Update kuantitas item di keranjang.
     */
    public function update(string $key, array $units): void
    {
        $cart = Session::get(self::SESSION_KEY, []);
        if (isset($cart[$key])) {
            $cart[$key]['units'] = $units;
            Session::put(self::SESSION_KEY, $cart);
        }
    }

    /**
     * Hapus satu produk dari keranjang.
     */
    public function remove(string $key): void
    {
        $cart = Session::get(self::SESSION_KEY, []);
        unset($cart[$key]);
        Session::put(self::SESSION_KEY, $cart);
    }

    /**
     * Kosongkan keranjang (misal setelah checkout berhasil).
     */
    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Jumlah variasi produk dalam keranjang.
     */
    public function count(): int
    {
        return count(Session::get(self::SESSION_KEY, []));
    }
}
