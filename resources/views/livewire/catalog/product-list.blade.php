<div>
    <!-- Hero Banner Stoqa -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-stoqa-800 via-stoqa-700 to-stoqa-900 text-white p-6 sm:p-8 md:p-10 mb-8 shadow-sm">
        <!-- Radial Glow Accent -->
        <div class="absolute -right-16 -top-16 w-80 h-80 bg-sand-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-80 h-80 bg-stoqa-400/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 max-w-3xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-bold text-sand-100 mb-4">
                <span class="w-2 h-2 rounded-full bg-sand-500 animate-pulse"></span>
                Platform Kulakan Multi-Satuan & Stok Real-Time
            </div>
            <h1 class="text-2xl sm:text-4xl font-black tracking-tight leading-tight text-white mb-3">
                Katalog Sembako & Grosir Terpercaya
            </h1>
            <p class="text-sm sm:text-base text-slate-200 leading-relaxed max-w-2xl mb-6">
                Belanja ecer maupun grosir kartonan dengan kalkulator harga bertingkat otomatis. Stok barang langsung di-hold begitu order dibuat untuk kepastian pasokan toko Anda.
            </p>

            <!-- 3 Pilar Keunggulan -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4 border-t border-white/15 text-xs text-slate-200">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-stoqa-500/30 flex items-center justify-center text-sand-500 font-bold shrink-0">✓</div>
                    <span>Stok FIFO Akurat & Real-time</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-stoqa-500/30 flex items-center justify-center text-sand-500 font-bold shrink-0">✓</div>
                    <span>Ongkir Dihitung Admin (Maks. 3 Jam)</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-stoqa-500/30 flex items-center justify-center text-sand-500 font-bold shrink-0">✓</div>
                    <span>Tenggat Pembayaran Aman 12 Jam</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="mb-8 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Daftar Barang Tersedia</h2>
                <p class="text-xs text-slate-500 mt-0.5">Pilih kategori atau cari barang yang ingin Anda kulak hari ini.</p>
            </div>

            <!-- Search input -->
            <div class="w-full md:w-80">
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari nama barang atau SKU..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/90 border border-slate-300 focus:border-stoqa-500 focus:ring-2 focus:ring-stoqa-100 text-sm outline-none transition shadow-xs"
                    >
                    <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Category Filter Chips -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
            <button
                type="button"
                wire:click="$set('category_id', null)"
                class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $category_id === null ? 'bg-stoqa-600 text-white shadow-xs' : 'bg-white/80 border border-slate-200/90 text-slate-700 hover:border-stoqa-300 hover:text-stoqa-700 hover:bg-white' }}"
            >
                Semua Kategori
            </button>
            @foreach ($categories as $cat)
                <button
                    type="button"
                    wire:click="$set('category_id', {{ $cat->id }})"
                    class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $category_id === $cat->id ? 'bg-stoqa-600 text-white shadow-xs' : 'bg-white/80 border border-slate-200/90 text-slate-700 hover:border-stoqa-300 hover:text-stoqa-700 hover:bg-white' }}"
                >
                    {{ $cat->name }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Product Grid -->
    @if ($products->isEmpty())
        <div class="py-16 text-center bg-white/80 backdrop-blur rounded-3xl border border-slate-200/90 p-8 shadow-xs">
            <div class="w-16 h-16 mx-auto mb-4 bg-stoqa-50 text-stoqa-600 rounded-2xl flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            </div>
            <h3 class="text-base font-extrabold text-slate-800">Tidak ada produk ditemukan</h3>
            <p class="text-xs text-slate-500 mt-1">Coba gunakan kata kunci lain atau pilih kategori berbeda.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach ($products as $product)
                @php
                    $available = max(0, $product->stockLevels->sum('on_hand') - $product->stockLevels->sum('reserved'));
                    $isLowStock = $available > 0 && $available <= $product->min_stock;
                    $isOutOfStock = $available <= 0;
                    $canSeePrice = $product->show_price_public || auth()->check();

                    // Tier harga terendah & ecer
                    $basePrice = $product->prices->where('unit.conversion_to_base', 1)->first() ?? $product->prices->first();
                @endphp

                <div class="bg-white/90 backdrop-blur rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden flex flex-col hover:shadow-lg hover:-translate-y-1 transition duration-200">
                    <!-- Card Header / Image placeholder -->
                    <div class="h-44 bg-gradient-to-tr from-stoqa-50 via-slate-100 to-sand-100/30 relative flex items-center justify-center p-4 border-b border-slate-100">
                        <span class="text-slate-400 font-black text-3xl tracking-wider select-none opacity-30">
                            {{ $product->sku }}
                        </span>

                        <!-- Stock Status Badge -->
                        <div class="absolute top-3 right-3">
                            @if ($isOutOfStock)
                                <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-1 shadow-xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    Stok Habis
                                </span>
                            @elseif ($isLowStock)
                                <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-amber-50 text-amber-800 border border-amber-200 flex items-center gap-1 shadow-xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    Sisa {{ $available }} {{ $product->base_unit }}
                                </span>
                            @else
                                <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center gap-1 shadow-xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Tersedia ({{ $available }} {{ $product->base_unit }})
                                </span>
                            @endif
                        </div>

                        <!-- Category tag -->
                        <div class="absolute bottom-3 left-3">
                            <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-lg bg-white/95 text-stoqa-800 shadow-xs backdrop-blur border border-slate-200/60">
                                {{ $product->category?->name ?? 'Umum' }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex flex-col flex-grow">
                        <div class="flex items-baseline justify-between gap-2 mb-1">
                            <span class="text-[11px] font-mono text-slate-400 font-semibold">{{ $product->sku }}</span>
                        </div>

                        <h2 class="font-extrabold text-slate-900 text-base leading-snug line-clamp-2 mb-3">
                            {{ $product->name }}
                        </h2>

                        <!-- Price Section -->
                        <div class="mt-auto pt-3 border-t border-slate-100">
                            @if ($canSeePrice)
                                @if ($basePrice)
                                    <div class="text-[11px] font-semibold text-slate-400">Harga Ecer Mulai</div>
                                    <div class="text-xl font-black text-stoqa-700">
                                        Rp {{ number_format($basePrice->price, 0, ',', '.') }}
                                        <span class="text-xs font-normal text-slate-500">/ {{ $basePrice->unit?->unit_name ?? $product->base_unit }}</span>
                                    </div>

                                    @if ($product->prices->count() > 1)
                                        <div class="mt-2 text-[11px] font-bold text-stoqa-700 bg-stoqa-50 border border-stoqa-200/60 px-2 py-1 rounded-md inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-sand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.12z" clip-rule="evenodd"/></svg>
                                            Grosir lebih hemat!
                                        </div>
                                    @endif
                                @else
                                    <div class="text-sm font-semibold text-slate-500">Hubungi Admin</div>
                                @endif
                            @else
                                <div class="py-2">
                                    <a href="{{ route('login') }}" class="text-xs font-bold text-stoqa-600 hover:text-stoqa-800 underline">
                                        Masuk untuk melihat harga
                                    </a>
                                </div>
                            @endif
                        </div>

                        <!-- CTA Button -->
                        <div class="mt-4">
                            <a
                                href="{{ url('/produk/' . $product->sku) }}"
                                wire:navigate
                                class="w-full block text-center py-2.5 px-4 rounded-xl text-xs sm:text-sm font-extrabold transition shadow-xs {{ $isOutOfStock ? 'bg-slate-100 text-slate-400 pointer-events-none' : 'bg-gradient-to-r from-stoqa-600 to-stoqa-500 hover:from-stoqa-700 hover:to-stoqa-600 text-white hover:shadow-md transform active:scale-98' }}"
                            >
                                {{ $isOutOfStock ? 'Stok Habis' : 'Kalkulator & Order' }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $products->links() }}
        </div>
    @endif
</div>
