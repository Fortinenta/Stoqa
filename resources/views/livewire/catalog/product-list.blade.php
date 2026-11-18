<div>
    <!-- Hero / Title & Search Bar -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Katalog Produk & Grosir</h1>
                <p class="text-sm text-slate-500 mt-1">Dapatkan harga bertingkat lebih hemat untuk ecer, pack, dan karton.</p>
            </div>

            <!-- Search input -->
            <div class="w-full md:w-80">
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari nama barang atau SKU..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-sm outline-none transition"
                    >
                    <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Category Filter Chips -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 mt-4 scrollbar-none">
            <button
                type="button"
                wire:click="$set('category_id', null)"
                class="px-4 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition {{ $category_id === null ? 'bg-blue-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100' }}"
            >
                Semua Kategori
            </button>
            @foreach ($categories as $cat)
                <button
                    type="button"
                    wire:click="$set('category_id', {{ $cat->id }})"
                    class="px-4 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition {{ $category_id === $cat->id ? 'bg-blue-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100' }}"
                >
                    {{ $cat->name }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Product Grid -->
    @if ($products->isEmpty())
        <div class="py-16 text-center bg-white rounded-2xl border border-slate-200 p-8">
            <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 rounded-full flex items-center justify-center text-slate-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">Tidak ada produk ditemukan</h3>
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

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col hover:shadow-md transition">
                    <!-- Card Header / Image placeholder -->
                    <div class="h-44 bg-gradient-to-tr from-slate-100 to-slate-200 relative flex items-center justify-center p-4">
                        <span class="text-slate-400 font-extrabold text-3xl tracking-wider select-none opacity-40">
                            {{ $product->sku }}
                        </span>

                        <!-- Stock Status Badge -->
                        <div class="absolute top-3 right-3">
                            @if ($isOutOfStock)
                                <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-rose-50 text-rose-700 border border-rose-200">
                                    Stok Habis
                                </span>
                            @elseif ($isLowStock)
                                <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-amber-50 text-amber-700 border border-amber-200">
                                    Sisa {{ $available }} {{ $product->base_unit }}
                                </span>
                            @else
                                <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Tersedia ({{ $available }} {{ $product->base_unit }})
                                </span>
                            @endif
                        </div>

                        <!-- Category tag -->
                        <div class="absolute bottom-3 left-3">
                            <span class="px-2 py-0.5 text-[10px] font-semibold rounded-md bg-white/90 text-slate-700 shadow-sm backdrop-blur">
                                {{ $product->category?->name ?? 'Umum' }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex flex-col flex-grow">
                        <div class="flex items-baseline justify-between gap-2 mb-1">
                            <span class="text-[11px] font-mono text-slate-400">{{ $product->sku }}</span>
                        </div>

                        <h2 class="font-bold text-slate-900 text-base leading-snug line-clamp-2 mb-3">
                            {{ $product->name }}
                        </h2>

                        <!-- Price Section -->
                        <div class="mt-auto pt-3 border-t border-slate-100">
                            @if ($canSeePrice)
                                @if ($basePrice)
                                    <div class="text-xs text-slate-400">Harga Ecer mulai</div>
                                    <div class="text-xl font-extrabold text-blue-600">
                                        Rp {{ number_format($basePrice->price, 0, ',', '.') }}
                                        <span class="text-xs font-normal text-slate-500">/ {{ $basePrice->unit?->unit_name ?? $product->base_unit }}</span>
                                    </div>

                                    @if ($product->prices->count() > 1)
                                        <div class="mt-2 text-[11px] font-medium text-emerald-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                            Tersedia harga grosir lebih hemat!
                                        </div>
                                    @endif
                                @else
                                    <div class="text-sm font-semibold text-slate-500">Hubungi Admin</div>
                                @endif
                            @else
                                <div class="py-2">
                                    <a href="{{ route('login') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 underline">
                                        Login untuk melihat harga
                                    </a>
                                </div>
                            @endif
                        </div>

                        <!-- CTA Button -->
                        <div class="mt-4">
                            <a
                                href="{{ url('/produk/' . $product->sku) }}"
                                wire:navigate
                                class="w-full block text-center py-2.5 px-4 rounded-xl text-sm font-bold transition shadow-sm {{ $isOutOfStock ? 'bg-slate-100 text-slate-400 pointer-events-none' : 'bg-slate-900 hover:bg-blue-600 text-white' }}"
                            >
                                {{ $isOutOfStock ? 'Habis' : 'Kalkulator & Order' }}
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
