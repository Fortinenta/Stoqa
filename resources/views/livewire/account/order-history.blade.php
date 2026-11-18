<div>
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Pesanan Saya</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">Pantau status pesanan, nominal tagihan ongkir, dan reservasi stok Anda.</p>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Tab Filter Status -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-6 border-b border-slate-200 text-xs font-bold scrollbar-none">
        <button
            type="button"
            wire:click="filterByStatus('all')"
            class="px-4 py-2 rounded-xl transition {{ $statusFilter === 'all' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}"
        >
            Semua Pesanan
        </button>
        <button
            type="button"
            wire:click="filterByStatus('pending')"
            class="px-4 py-2 rounded-xl transition {{ $statusFilter === 'pending' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}"
        >
            Menunggu (Ongkir / Bayar)
        </button>
        <button
            type="button"
            wire:click="filterByStatus('paid')"
            class="px-4 py-2 rounded-xl transition {{ $statusFilter === 'paid' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}"
        >
            Diproses / Lunas
        </button>
        <button
            type="button"
            wire:click="filterByStatus('cancelled')"
            class="px-4 py-2 rounded-xl transition {{ $statusFilter === 'cancelled' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}"
        >
            Dibatalkan / Expired
        </button>
    </div>

    @if ($orders->isEmpty())
        <div class="py-16 text-center bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
            <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 rounded-full flex items-center justify-center text-slate-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">Belum ada pesanan pada filter ini</h3>
            <p class="text-xs text-slate-500 mt-1">Silakan cek katalog produk untuk mulai berbelanja.</p>
            <a href="{{ url('/') }}" class="inline-block mt-4 px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-sm">
                Lihat Katalog
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($orders as $order)
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-extrabold text-slate-900">
                                {{ $order->invoice_no ?? ('Order #' . $order->id) }}
                            </span>
                            <span class="text-[11px] text-slate-400 font-medium">
                                {{ $order->created_at->format('d M Y, H:i') }}
                            </span>
                        </div>

                        <!-- Status Badge -->
                        <div>
                            @php
                                $status = $order->status;
                                $statusEnum = $status instanceof \App\Enums\OrderStatus ? $status : \App\Enums\OrderStatus::tryFrom((string) $status);
                                $badgeClass = match($statusEnum) {
                                    \App\Enums\OrderStatus::MENUNGGU_ONGKIR => 'bg-amber-50 text-amber-700 border-amber-200',
                                    \App\Enums\OrderStatus::PENDING_PAYMENT => 'bg-blue-50 text-blue-700 border-blue-200',
                                    \App\Enums\OrderStatus::PAID => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    \App\Enums\OrderStatus::SHIPPED => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    \App\Enums\OrderStatus::COMPLETED => 'bg-slate-100 text-slate-700 border-slate-200',
                                    \App\Enums\OrderStatus::CANCELLED, \App\Enums\OrderStatus::EXPIRED, \App\Enums\OrderStatus::REJECTED => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-slate-50 text-slate-600 border-slate-200',
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border {{ $badgeClass }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ str_contains($badgeClass, 'amber') ? 'bg-amber-500' : (str_contains($badgeClass, 'blue') ? 'bg-blue-500' : (str_contains($badgeClass, 'emerald') ? 'bg-emerald-500' : 'bg-rose-500')) }}"></span>
                                {{ $statusEnum?->label() ?? (is_string($status) ? $status : $status?->value ?? '-') }}
                            </span>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="py-3 divide-y divide-slate-50 space-y-2">
                        @foreach ($order->items as $item)
                            <div class="pt-2 first:pt-0 flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-bold text-slate-800">{{ $item->product->name ?? 'Produk' }}</span>
                                    <span class="text-slate-400 ml-1">x {{ $item->qty }} {{ $item->unit->name ?? 'pcs' }}</span>
                                </div>
                                <span class="font-bold text-slate-700">Rp {{ number_format($item->line_total, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Ringkasan Keuangan & Ongkir -->
                    <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="text-xs text-slate-500 space-y-1">
                            <div>
                                Subtotal: <strong class="text-slate-700">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</strong>
                                | Ongkir:
                                @if ($order->status === \App\Enums\OrderStatus::MENUNGGU_ONGKIR)
                                    <span class="text-amber-600 font-bold">Sedang dihitung admin</span>
                                @else
                                    <strong class="text-slate-700">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</strong>
                                @endif
                            </div>

                            @if ($order->status === \App\Enums\OrderStatus::MENUNGGU_ONGKIR && $order->shipping_due_at)
                                <p class="text-[11px] text-amber-700 font-medium flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Batas input ongkir admin: {{ $order->shipping_due_at->format('H:i') }} WIB (Stok aman di-hold)
                                </p>
                            @elseif ($order->status === \App\Enums\OrderStatus::PENDING_PAYMENT && $order->expires_at)
                                <p class="text-[11px] text-blue-700 font-medium flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Batas bayar sebelum hold expired: {{ $order->expires_at->format('d M H:i') }} WIB
                                </p>
                            @endif
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-4">
                            <div class="text-right">
                                <span class="text-[10px] text-slate-400 block uppercase font-bold tracking-wider">Total Tagihan</span>
                                <span class="text-base font-black text-slate-900">
                                    Rp {{ number_format($order->total, 0, ',', '.') }}
                                </span>
                            </div>

                            @if (in_array($order->status, [\App\Enums\OrderStatus::MENUNGGU_ONGKIR, \App\Enums\OrderStatus::PENDING_PAYMENT]))
                                <button
                                    type="button"
                                    wire:confirm="Yakin ingin membatalkan pesanan ini? Stok yang ditahan akan dilepaskan kembali ke publik."
                                    wire:click="cancelOrder({{ $order->id }})"
                                    class="text-xs px-3 py-2 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold transition"
                                >
                                    Batalkan
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="pt-4">
                {{ $orders->links() }}
            </div>
        </div>
    @endif
</div>
