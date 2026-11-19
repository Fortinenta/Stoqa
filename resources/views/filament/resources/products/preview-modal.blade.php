@php
    $onHand = $product->stockLevels->sum('on_hand');
    $reserved = $product->stockLevels->sum('reserved');
    $available = max(0, $onHand - $reserved);

    // Ambil reservasi aktif yang sedang menahan stok ini
    $activeReservations = \App\Models\StockReservation::with(['order.user'])
        ->where('product_id', $product->id)
        ->where('status', \App\Enums\ReservationStatus::ACTIVE)
        ->get();

    // Ambil batch stok yang masih memiliki sisa fisik (FIFO)
    $activeBatches = \App\Models\StockBatch::with('warehouse')
        ->where('product_id', $product->id)
        ->where('qty_remaining', '>', 0)
        ->orderBy('received_at', 'asc')
        ->get();
@endphp

<div class="space-y-6 text-slate-800 dark:text-slate-200">
    <!-- 1. Header Informasi Produk -->
    <div class="bg-slate-50 dark:bg-slate-800/60 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-700">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                    <span class="text-xs font-mono font-bold px-2.5 py-0.5 rounded-md bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                        SKU: {{ $product->sku }}
                    </span>
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-md bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                        {{ $product->category->name ?? 'Tanpa Kategori' }}
                    </span>
                    @if ($product->is_active)
                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                        </span>
                    @else
                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-md bg-rose-50 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Nonaktif
                        </span>
                    @endif
                    @if ($product->show_price_public)
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                            Harga Tampil Publik
                        </span>
                    @endif
                </div>
                <h3 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white leading-snug">
                    {{ $product->name }}
                </h3>
            </div>
            <div class="text-left sm:text-right flex-shrink-0">
                <span class="text-[11px] uppercase tracking-wider font-bold text-slate-400 block">Satuan Dasar</span>
                <span class="text-sm font-extrabold text-slate-700 dark:text-slate-300">{{ $product->base_unit }}</span>
            </div>
        </div>

        @if ($product->description)
            <div class="mt-3 pt-3 border-t border-slate-200/80 dark:border-slate-700/80 text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                {{ $product->description }}
            </div>
        @endif
    </div>

    <!-- 2. Kartu Status Stok (3 Metrik Utama) -->
    <div>
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2.5 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            Ringkasan Persediaan Gudang
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- Stok Fisik -->
            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 block">Stok Fisik di Gudang</span>
                <div class="mt-1 flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($onHand) }}</span>
                    <span class="text-xs font-bold text-slate-400">{{ $product->base_unit }}</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Total unit barang yang ada di rak</p>
            </div>

            <!-- Stok Di-Hold -->
            <div class="p-4 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/50 dark:bg-amber-950/20">
                <span class="text-xs font-bold text-amber-800 dark:text-amber-400 block">Sedang Tertahan (Di-Hold)</span>
                <div class="mt-1 flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ number_format($reserved) }}</span>
                    <span class="text-xs font-bold text-amber-700/70 dark:text-amber-400/70">{{ $product->base_unit }}</span>
                </div>
                <p class="text-[11px] text-amber-700/80 dark:text-amber-400/80 mt-1">Dipesan pembeli (menunggu ongkir/bayar)</p>
            </div>

            <!-- Stok Tersedia Bebas -->
            <div class="p-4 rounded-xl border @if($available <= 0) border-rose-200 dark:border-rose-800 bg-rose-50/50 dark:bg-rose-950/20 @elseif($available <= $product->min_stock) border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-950/20 @else border-emerald-200 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-950/20 @endif">
                <span class="text-xs font-bold @if($available <= 0) text-rose-700 dark:text-rose-400 @elseif($available <= $product->min_stock) text-amber-700 dark:text-amber-400 @else text-emerald-700 dark:text-emerald-400 @endif block">
                    Stok Tersedia Dijual
                </span>
                <div class="mt-1 flex items-baseline gap-1.5">
                    <span class="text-2xl font-black @if($available <= 0) text-rose-600 dark:text-rose-400 @elseif($available <= $product->min_stock) text-amber-600 dark:text-amber-400 @else text-emerald-600 dark:text-emerald-400 @endif">
                        {{ number_format($available) }}
                    </span>
                    <span class="text-xs font-bold text-slate-400">{{ $product->base_unit }}</span>
                </div>
                <p class="text-[11px] @if($available <= 0) text-rose-600 @elseif($available <= $product->min_stock) text-amber-600 @else text-emerald-600 @endif mt-1">
                    @if($available <= 0)
                        Habis! Tidak bisa dibeli publik
                    @elseif($available <= $product->min_stock)
                        Stok menipis (di bawah batas {{ $product->min_stock }})
                    @else
                        Stok aman untuk dijual bebas
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- 3. Rincian Stok Tertahan (Siapa yang Menahan & Mengapa) -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 sm:p-5">
        <h4 class="text-sm font-extrabold text-slate-900 dark:text-white mb-1 flex items-center justify-between">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Rincian Pesanan yang Sedang Menahan Stok (Hold)
            </span>
            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-300">
                Total Hold: {{ number_format($reserved) }} {{ $product->base_unit }}
            </span>
        </h4>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Daftar order aktif yang saat ini mengunci kuota produk ini agar tidak tertimpa pembeli lain.</p>

        @if ($activeReservations->isEmpty())
            <div class="py-6 text-center rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-800/40 text-emerald-700 dark:text-emerald-300 text-xs font-semibold flex items-center justify-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Tidak ada pesanan yang sedang menahan stok. Seluruh stok fisik tersedia bebas untuk dijual.</span>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 text-slate-400 uppercase text-[10px] font-extrabold">
                            <th class="py-2.5 px-3">No. Order / Invoice</th>
                            <th class="py-2.5 px-3">Nama Pembeli</th>
                            <th class="py-2.5 px-3">Status Order</th>
                            <th class="py-2.5 px-3 text-right">Jumlah Di-Hold</th>
                            <th class="py-2.5 px-3 text-right">Tenggat Waktu Hold</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-medium">
                        @foreach ($activeReservations as $res)
                            @php
                                $order = $res->order;
                                $statusEnum = $order?->status;
                            @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/40 transition">
                                <td class="py-2.5 px-3 font-mono font-bold text-blue-600 dark:text-blue-400">
                                    {{ $order?->invoice_no ?? ('Order #' . $order?->id) }}
                                    @if ($order?->invoice_no)
                                        <span class="text-[10px] text-slate-400 block font-sans font-normal">Order #{{ $order?->id }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-slate-800 dark:text-slate-200 font-bold">
                                    {{ $order?->customer_name ?? $order?->user?->name ?? 'Pembeli Umum' }}
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold @if($statusEnum === \App\Enums\OrderStatus::MENUNGGU_ONGKIR) bg-amber-100 text-amber-800 @else bg-blue-100 text-blue-800 @endif">
                                        {{ $statusEnum?->label() ?? 'Menunggu' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-right font-extrabold text-amber-600 dark:text-amber-400">
                                    {{ number_format($res->qty_base) }} {{ $product->base_unit }}
                                </td>
                                <td class="py-2.5 px-3 text-right text-slate-500">
                                    @if ($res->expires_at)
                                        <span class="font-bold {{ $res->expires_at->isPast() ? 'text-rose-600' : 'text-slate-700 dark:text-slate-300' }}">
                                            {{ $res->expires_at->format('d M H:i') }} WIB
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- 4. Rincian Batch Masuk (FIFO / HPP) -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 sm:p-5">
        <h4 class="text-sm font-extrabold text-slate-900 dark:text-white mb-1 flex items-center justify-between">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Rincian Batch Masuk (Antrean FIFO & HPP)
            </span>
            <span class="text-xs text-slate-400">
                {{ $activeBatches->count() }} Batch Aktif
            </span>
        </h4>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Antrean batch fisik di rak gudang. Batch paling awal akan dipotong terlebih dahulu saat penjualan.</p>

        @if ($activeBatches->isEmpty())
            <div class="py-4 text-center rounded-xl bg-slate-50 dark:bg-slate-700/50 text-slate-400 text-xs">
                Belum ada batch penerimaan barang masuk untuk produk ini.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 text-slate-400 uppercase text-[10px] font-extrabold">
                            <th class="py-2.5 px-3">Batch ID / Gudang</th>
                            <th class="py-2.5 px-3">Tgl Masuk (FIFO)</th>
                            <th class="py-2.5 px-3 text-right">Qty Awal</th>
                            <th class="py-2.5 px-3 text-right">Sisa Fisik</th>
                            <th class="py-2.5 px-3 text-right">HPP Satuan (Landed Cost)</th>
                            <th class="py-2.5 px-3 text-right">Subtotal Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-medium">
                        @foreach ($activeBatches as $batch)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/40 transition">
                                <td class="py-2 px-3 font-mono text-slate-700 dark:text-slate-300">
                                    <span class="font-bold">#BATCH-{{ str_pad($batch->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    <span class="text-[11px] text-slate-400 block">{{ $batch->warehouse->name ?? 'Gudang Utama' }}</span>
                                </td>
                                <td class="py-2 px-3 text-slate-700 dark:text-slate-300">
                                    {{ $batch->received_at?->format('d M Y H:i') ?? '-' }}
                                </td>
                                <td class="py-2 px-3 text-right text-slate-500">
                                    {{ number_format($batch->qty_initial) }} {{ $product->base_unit }}
                                </td>
                                <td class="py-2 px-3 text-right font-extrabold text-slate-900 dark:text-white">
                                    {{ number_format($batch->qty_remaining) }} {{ $product->base_unit }}
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-slate-700 dark:text-slate-300">
                                    Rp {{ number_format($batch->unit_cost_landed, 0, ',', '.') }}
                                </td>
                                <td class="py-2 px-3 text-right font-mono text-slate-600 dark:text-slate-400">
                                    Rp {{ number_format($batch->qty_remaining * $batch->unit_cost_landed, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- 5. Satuan Kemasan & Tier Harga Bertingkat -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Satuan Kemasan -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
            <h5 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Satuan Kemasan Dijual</h5>
            <div class="space-y-1.5 text-xs">
                <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 dark:bg-slate-700/50 font-bold">
                    <span>{{ $product->base_unit }} (Satuan Dasar)</span>
                    <span class="text-slate-500 font-mono">1 {{ $product->base_unit }}</span>
                </div>
                @foreach ($product->units as $u)
                    @if ($u->conversion_to_base > 1)
                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                            <span class="font-medium text-slate-800 dark:text-slate-200">{{ $u->unit_name }}</span>
                            <span class="text-blue-600 dark:text-blue-400 font-mono font-bold">= {{ $u->conversion_to_base }} {{ $product->base_unit }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- Tier Harga -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
            <h5 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Daftar Tier Harga Jual</h5>
            <div class="space-y-1.5 text-xs">
                @forelse ($product->prices as $p)
                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                        <div>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $p->tier_name }}</span>
                            <span class="text-slate-400 ml-1">({{ $p->unit?->unit_name ?? $product->base_unit }}, Min. {{ $p->min_qty }})</span>
                        </div>
                        <span class="font-extrabold text-slate-900 dark:text-white font-mono">
                            Rp {{ number_format($p->price, 0, ',', '.') }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 p-2">Belum ada tier harga khusus yang diinput.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
