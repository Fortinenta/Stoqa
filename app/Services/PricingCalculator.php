<?php

namespace App\Services;

use App\Models\CustomerPrice;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;

class PricingCalculator
{
    /**
     * Hitung harga terbaik untuk suatu produk berdasarkan kuantitas satuan dasar dan pembeli.
     *
     * @param  int  $qtyBase  Jumlah dalam satuan dasar (misal pcs)
     * @param  User|null  $user  Pengguna yang sedang login (untuk pengecekan harga khusus)
     * @return array [
     *               'unit_price' => float,
     *               'total_price' => float,
     *               'price_source' => string, // 'customer' | 'tier' | 'base'
     *               'applied_tier' => string|null,
     *               'breakdown' => array,
     *               ]
     */
    public function calculateBestPrice(Product $product, int $qtyBase, ?User $user = null): array
    {
        if ($qtyBase <= 0) {
            return [
                'unit_price' => 0.0,
                'total_price' => 0.0,
                'price_source' => 'base',
                'applied_tier' => null,
                'breakdown' => [],
            ];
        }

        // 1. Cek apakah ada harga khusus pelanggan langganan (customer_prices)
        if ($user) {
            $customerPrice = CustomerPrice::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->where('min_qty', '<=', $qtyBase)
                ->where(function ($q) {
                    $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()->toDateString());
                })
                ->with('unit')
                ->orderBy('min_qty', 'desc')
                ->first();

            if ($customerPrice) {
                $conversion = $customerPrice->unit ? $customerPrice->unit->conversion_to_base : 1;
                $effectiveUnitPriceInBase = (float) $customerPrice->price / $conversion;
                $totalPrice = round($effectiveUnitPriceInBase * $qtyBase, 2);

                return [
                    'unit_price' => round($effectiveUnitPriceInBase, 2),
                    'total_price' => $totalPrice,
                    'price_source' => 'customer',
                    'applied_tier' => 'Khusus Pelanggan ('.($customerPrice->unit?->unit_name ?? $product->base_unit).')',
                    'breakdown' => [
                        [
                            'unit_name' => $customerPrice->unit?->unit_name ?? $product->base_unit,
                            'conversion' => $conversion,
                            'price' => (float) $customerPrice->price,
                            'qty_base' => $qtyBase,
                            'subtotal' => $totalPrice,
                        ],
                    ],
                ];
            }
        }

        // 2. Ambil tier harga standar produk (product_prices)
        $prices = ProductPrice::where('product_id', $product->id)
            ->where(function ($q) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()->toDateString());
            })
            ->with('unit')
            ->get();

        if ($prices->isEmpty()) {
            return [
                'unit_price' => 0.0,
                'total_price' => 0.0,
                'price_source' => 'base',
                'applied_tier' => null,
                'breakdown' => [],
            ];
        }

        // 3. Algoritma pemecahan satuan terbesar dan pencarian kombinasi termurah
        // Hitung biaya per satuan dasar untuk setiap opsi tier
        $tierOptions = [];
        foreach ($prices as $p) {
            $conversion = $p->unit ? $p->unit->conversion_to_base : 1;
            $minBaseQty = $p->min_qty * $conversion;
            $pricePerBaseUnit = (float) $p->price / $conversion;

            $tierOptions[] = [
                'model' => $p,
                'tier_name' => $p->tier_name ?? ($p->unit?->unit_name ?? $product->base_unit),
                'unit_name' => $p->unit?->unit_name ?? $product->base_unit,
                'conversion' => $conversion,
                'min_qty' => $p->min_qty,
                'min_base_qty' => $minBaseQty,
                'price_per_unit' => (float) $p->price,
                'price_per_base' => $pricePerBaseUnit,
            ];
        }

        // Urutkan opsi berdasarkan konversi terbesar ke terkecil
        usort($tierOptions, function ($a, $b) {
            if ($a['conversion'] === $b['conversion']) {
                return $a['price_per_base'] <=> $b['price_per_base'];
            }

            return $b['conversion'] <=> $a['conversion'];
        });

        // Opsi A: Greedy dari satuan terbesar yang memenuhi min_qty
        $remaining = $qtyBase;
        $greedyBreakdown = [];
        $greedyTotal = 0.0;

        foreach ($tierOptions as $opt) {
            if ($remaining <= 0) {
                break;
            }

            if ($remaining >= $opt['min_base_qty']) {
                $unitsCount = intdiv($remaining, $opt['conversion']);
                if ($unitsCount >= $opt['min_qty']) {
                    $usedBase = $unitsCount * $opt['conversion'];
                    $subtotal = $unitsCount * $opt['price_per_unit'];

                    $greedyBreakdown[] = [
                        'tier_name' => $opt['tier_name'],
                        'unit_name' => $opt['unit_name'],
                        'units_count' => $unitsCount,
                        'conversion' => $opt['conversion'],
                        'qty_base' => $usedBase,
                        'unit_price' => $opt['price_per_unit'],
                        'subtotal' => $subtotal,
                    ];

                    $greedyTotal += $subtotal;
                    $remaining -= $usedBase;
                }
            }
        }

        // Jika masih ada sisa yang belum ter-cover (misal sisa ecer di bawah min_qty tier grosir),
        // gunakan tier ecer/satuan terkecil
        if ($remaining > 0) {
            // Cari tier dengan satuan dasar (conversion = 1) atau harga per base terendah yang valid
            $baseTier = null;
            foreach ($tierOptions as $opt) {
                if ($opt['conversion'] === 1 && $remaining >= $opt['min_qty']) {
                    $baseTier = $opt;
                    break;
                }
            }

            if (! $baseTier) {
                // Ambil tier terkecil yang ada
                $baseTier = end($tierOptions);
            }

            $subtotal = $remaining * $baseTier['price_per_base'];
            $greedyBreakdown[] = [
                'tier_name' => $baseTier['tier_name'] ?? 'Ecer',
                'unit_name' => $baseTier['unit_name'],
                'units_count' => $remaining,
                'conversion' => 1,
                'qty_base' => $remaining,
                'unit_price' => $baseTier['price_per_base'],
                'subtotal' => round($subtotal, 2),
            ];
            $greedyTotal += $subtotal;
        }

        // Opsi B: Periksa apakah membeli dengan tier satuan lebih besar secara pembulatan ke atas lebih murah
        // (contoh: beli 11 pcs ecer @15rb = 165rb vs 1 pack isi 12 pcs @150rb)
        $bestTotal = $greedyTotal;
        $bestBreakdown = $greedyBreakdown;
        $bestTierName = $greedyBreakdown[0]['tier_name'] ?? 'Standar';

        foreach ($tierOptions as $opt) {
            if ($opt['conversion'] > 1) {
                $roundedUpUnits = (int) ceil($qtyBase / $opt['conversion']);
                if ($roundedUpUnits >= $opt['min_qty']) {
                    $bulkTotal = $roundedUpUnits * $opt['price_per_unit'];
                    if ($bulkTotal < $bestTotal) {
                        $bestTotal = $bulkTotal;
                        $bestTierName = $opt['tier_name'];
                        $bestBreakdown = [
                            [
                                'tier_name' => $opt['tier_name'],
                                'unit_name' => $opt['unit_name'],
                                'units_count' => $roundedUpUnits,
                                'conversion' => $opt['conversion'],
                                'qty_base' => $roundedUpUnits * $opt['conversion'],
                                'unit_price' => $opt['price_per_unit'],
                                'subtotal' => $bulkTotal,
                            ],
                        ];
                    }
                }
            }
        }

        return [
            'unit_price' => round($bestTotal / $qtyBase, 2),
            'total_price' => round($bestTotal, 2),
            'price_source' => 'tier',
            'applied_tier' => $bestTierName,
            'breakdown' => $bestBreakdown,
        ];
    }
}
