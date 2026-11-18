<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Keranjang Belanja</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Periksa pesanan Anda sebelum melanjutkan ke proses checkout.</p>
        </div>

        @if (!empty($cart['items']))
            <button
                type="button"
                wire:click="clearCart"
                class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition"
            >
                Kosongkan Keranjang
            </button>
        @endif
    </div>

    @if (empty($cart['items']))
        <div class="py-16 text-center bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
            <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 rounded-full flex items-center justify-center text-slate-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">Keranjang Anda masih kosong</h3>
            <p class="text-xs text-slate-500 mt-1">Jelajahi produk di katalog kami dan nikmati diskon harga bertingkat.</p>
            <a href="{{ url('/') }}" wire:navigate class="inline-block mt-4 px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-sm">
                Buka Katalog Produk
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Kolom Kiri: Daftar Item Keranjang -->
            <div class="lg:col-span-8 space-y-4">
                @foreach ($cart['items'] as $item)
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex-grow">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[11px] font-mono text-slate-400">{{ $item['sku'] }}</span>
                                @if ($item['applied_tier'])
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700">
                                        Tier: {{ $item['applied_tier'] }}
                                    </span>
                                @endif
                            </div>

                            <h3 class="text-base font-bold text-slate-900 leading-snug">
                                {{ $item['product_name'] }}
                            </h3>

                            <!-- Rincian Satuan yang Dibeli -->
                            <div class="mt-2 text-xs text-slate-600 flex flex-wrap gap-2">
                                @foreach ($item['units_breakdown'] as $ub)
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200 font-medium">
                                        {{ $ub['qty'] }} {{ $ub['unit_name'] }}
                                    </span>
                                @endforeach
                                <span class="text-slate-400 font-semibold self-center">
                                    (= {{ $item['total_base_qty'] }} {{ $item['base_unit'] }})
                                </span>
                            </div>

                            @if ($item['savings'] > 0)
                                <div class="mt-2 text-[11px] font-semibold text-emerald-600 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Hemat Rp {{ number_format($item['savings'], 0, ',', '.') }} dengan tier grosir
                                </div>
                            @endif
                        </div>

                        <!-- Harga & Hapus -->
                        <div class="flex sm:flex-col items-center sm:items-end justify-between border-t sm:border-t-0 pt-3 sm:pt-0 border-slate-100 gap-2">
                            <div class="text-right">
                                <div class="text-xs text-slate-400">Total Harga Item</div>
                                <div class="text-lg font-extrabold text-slate-900">
                                    Rp {{ number_format($item['total_price'], 0, ',', '.') }}
                                </div>
                            </div>

                            <button
                                type="button"
                                wire:click="removeItem('{{ $item['key'] }}')"
                                class="text-xs text-rose-500 hover:text-rose-700 font-semibold p-1.5 flex items-center gap-1"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                <span>Hapus</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Kolom Kanan: Ringkasan Belanja -->
            <div class="lg:col-span-4">
                <div class="sticky top-20 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
                    <h2 class="text-base font-extrabold text-slate-900">Ringkasan Order</h2>

                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between text-slate-600">
                            <span>Total Item Produk:</span>
                            <span class="font-bold text-slate-900">{{ $cart['total_items'] }} Macam</span>
                        </div>

                        <div class="flex justify-between text-slate-600">
                            <span>Total Kuantitas Fisik:</span>
                            <span class="font-bold text-slate-900">{{ $cart['total_base_qty'] }} Satuan Dasar</span>
                        </div>

                        @if ($cart['total_savings'] > 0)
                            <div class="flex justify-between text-emerald-700 bg-emerald-50 p-2.5 rounded-xl border border-emerald-200">
                                <span class="font-semibold">Total Penghematan Tier:</span>
                                <span class="font-black">Rp {{ number_format($cart['total_savings'], 0, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="pt-3 border-t border-slate-200 flex justify-between items-baseline">
                            <span class="text-sm font-bold text-slate-800">Subtotal Belanja:</span>
                            <span class="text-xl font-black text-blue-600">
                                Rp {{ number_format($cart['subtotal'], 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="text-[11px] text-slate-400 bg-slate-50 p-3 rounded-xl border border-slate-200/80 leading-relaxed">
                            💡 <em>Ongkos kirim akan dihitung oleh admin dalam waktu 3 jam setelah checkout, dan stok akan ditahan (hold) agar pesanan Anda aman.</em>
                        </div>
                    </div>

                    <a
                        href="{{ url('/checkout') }}"
                        wire:navigate
                        class="w-full block text-center py-3.5 px-4 rounded-xl text-sm font-bold bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white shadow-sm transition"
                    >
                        Lanjut ke Checkout
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
