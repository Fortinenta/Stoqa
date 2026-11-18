<div>
    <!-- Breadcrumb & Back -->
    <div class="mb-6">
        <a href="{{ url('/') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-blue-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke Katalog
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Kolom Kiri: Informasi Produk & Daftar Tier Harga -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Header Kartu Produk -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-blue-50 text-blue-700">
                        {{ $product->category?->name ?? 'Umum' }}
                    </span>
                    <span class="text-xs font-mono text-slate-400">SKU: {{ $product->sku }}</span>
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-3">
                    {{ $product->name }}
                </h1>

                <!-- Status Ketersediaan Stok -->
                <div class="flex items-center gap-2 p-3 rounded-xl bg-slate-50 border border-slate-200/80 mb-4">
                    <div class="w-3 h-3 rounded-full {{ $availableStock > 0 ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></div>
                    <span class="text-xs font-semibold text-slate-700">
                        Stok Tersedia: <strong class="text-slate-900">{{ $availableStock }} {{ $product->base_unit }}</strong>
                    </span>
                    @if ($availableStock <= $product->min_stock && $availableStock > 0)
                        <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800">
                            Stok Terbatas
                        </span>
                    @endif
                </div>

                @if ($product->description)
                    <div class="prose prose-sm text-slate-600 mt-4">
                        <p>{{ $product->description }}</p>
                    </div>
                @endif
            </div>

            <!-- Tabel Tier Harga Standar -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    Daftar Harga Bertingkat
                </h3>
                <p class="text-xs text-slate-500 mb-4">Semakin banyak pesanan Anda, semakin murah harga per satuannya.</p>

                @if ($canSeePrice)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider font-semibold">
                                    <th class="py-2.5 px-3">Tier</th>
                                    <th class="py-2.5 px-3">Satuan</th>
                                    <th class="py-2.5 px-3">Min. Pembelian</th>
                                    <th class="py-2.5 px-3 text-right">Harga per Satuan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($product->prices as $price)
                                    <tr class="hover:bg-slate-50">
                                        <td class="py-3 px-3 font-bold text-slate-900">{{ $price->tier_name ?? '-' }}</td>
                                        <td class="py-3 px-3 text-slate-600">
                                            {{ $price->unit?->unit_name ?? $product->base_unit }}
                                            @if ($price->unit && $price->unit->conversion_to_base > 1)
                                                <span class="text-[10px] text-slate-400">({{ $price->unit->conversion_to_base }} {{ $product->base_unit }})</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-3 font-semibold text-slate-700">{{ $price->min_qty }} {{ $price->unit?->unit_name ?? $product->base_unit }}</td>
                                        <td class="py-3 px-3 text-right font-extrabold text-blue-600">
                                            Rp {{ number_format($price->price, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-4 text-center text-slate-400">Belum ada tier harga yang diatur.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @auth
                        @php
                            $userCustomPrice = $product->customerPrices->where('user_id', auth()->id())->first();
                        @endphp
                        @if ($userCustomPrice)
                            <div class="mt-4 p-3 rounded-xl bg-amber-50 border border-amber-200 flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 011.316 1.316l-.8 1.599L18.677 11H20a1 1 0 110 2h-1.323l-1.582 3.954.8 1.599a1 1 0 01-1.316 1.316l-1.599-.8L11 18.677V20a1 1 0 11-2 0v-1.323l-3.954-1.582-1.599.8a1 1 0 01-1.316-1.316l.8-1.599L1.323 13H0a1 1 0 110-2h1.323l1.582-3.954-.8-1.599a1 1 0 011.316-1.316l1.599.8L9 3.323V2a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                                <div class="text-xs text-amber-900">
                                    <strong class="font-bold">Harga Khusus Langganan Aktif:</strong> Anda mendapat harga khusus <strong>Rp {{ number_format($userCustomPrice->price, 0, ',', '.') }}</strong> per {{ $userCustomPrice->unit?->unit_name ?? $product->base_unit }}.
                                </div>
                            </div>
                        @endif
                    @endauth
                @else
                    <div class="py-6 text-center bg-slate-50 rounded-xl p-4">
                        <p class="text-sm font-semibold text-slate-700">Harga hanya dapat dilihat oleh pengguna terdaftar.</p>
                        <a href="{{ route('login') }}" class="inline-block mt-3 px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 shadow-sm transition">
                            Login Sekarang
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Kolom Kanan: Kalkulator Order Interaktif -->
        <div class="lg:col-span-5">
            <div class="sticky top-20 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-6">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Kalkulator Order
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">Masukkan jumlah pesanan per satuan. Sistem akan memilihkan total termurah secara otomatis.</p>
                </div>

                @if ($canSeePrice)
                    <!-- Form Input Satuan -->
                    <div class="space-y-3">
                        @foreach ($product->units as $unit)
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                                <div>
                                    <div class="text-sm font-bold text-slate-800 capitalize">{{ $unit->unit_name }}</div>
                                    @if ($unit->conversion_to_base > 1)
                                        <div class="text-[11px] text-slate-500">1 {{ $unit->unit_name }} = {{ $unit->conversion_to_base }} {{ $product->base_unit }}</div>
                                    @else
                                        <div class="text-[11px] text-slate-500">Satuan dasar terkecil</div>
                                    @endif
                                </div>

                                <div class="w-32">
                                    <div class="flex items-center">
                                        <input
                                            type="number"
                                            min="0"
                                            wire:model.live.debounce.250ms="quantities.{{ $unit->id }}"
                                            class="w-full text-center py-2 px-3 rounded-lg border border-slate-300 text-sm font-bold focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                                            placeholder="0"
                                        >
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Ringkasan Hasil Kalkulator -->
                    <div class="p-4 rounded-xl bg-gradient-to-tr from-slate-900 to-slate-800 text-white space-y-3">
                        <div class="flex items-center justify-between text-xs text-slate-300">
                            <span>Total Barang Dihitung:</span>
                            <span class="font-bold text-white text-sm">{{ $totalBaseQty }} {{ $product->base_unit }}</span>
                        </div>

                        @if ($appliedTier)
                            <div class="flex items-center justify-between text-xs text-slate-300">
                                <span>Tier Diterapkan:</span>
                                <span class="font-semibold text-emerald-400">{{ $appliedTier }}</span>
                            </div>
                        @endif

                        @if ($savings > 0)
                            <div class="flex items-center justify-between text-xs text-emerald-300 bg-emerald-950/60 p-2 rounded-lg border border-emerald-500/20">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Hemat Tier:
                                </span>
                                <span class="font-extrabold text-emerald-400">Rp {{ number_format($savings, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="pt-2 border-t border-slate-700 flex items-baseline justify-between">
                            <span class="text-xs font-semibold text-slate-300">Total Harga:</span>
                            <span class="text-2xl font-black text-white">
                                Rp {{ number_format($subtotal, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <!-- Alert Validasi Stok -->
                    @if ($totalBaseQty > $availableStock)
                        <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <span>Jumlah pesanan melebihi stok yang tersedia (Maks. {{ $availableStock }} {{ $product->base_unit }}).</span>
                        </div>
                    @endif

                    <!-- Tombol Masukkan Keranjang -->
                    <button
                        type="button"
                        wire:click="addToCart"
                        wire:loading.attr="disabled"
                        @disabled($totalBaseQty <= 0 || $totalBaseQty > $availableStock)
                        class="w-full py-3.5 px-4 rounded-xl text-sm font-bold text-white transition shadow-sm flex items-center justify-center gap-2 {{ $totalBaseQty <= 0 || $totalBaseQty > $availableStock ? 'bg-slate-300 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700 active:scale-[0.98]' }}"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span wire:loading.remove>+ Masukkan ke Keranjang</span>
                        <span wire:loading>Menambahkan...</span>
                    </button>
                @else
                    <div class="py-8 text-center bg-slate-50 rounded-xl p-4">
                        <p class="text-xs font-semibold text-slate-600 mb-3">Silakan login untuk menghitung kalkulasi dan melakukan pemesanan.</p>
                        <a href="{{ route('login') }}" class="w-full block py-2.5 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 transition">
                            Masuk Akun
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
