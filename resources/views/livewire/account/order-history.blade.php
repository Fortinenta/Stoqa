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

                            @php
                                $failedPayment = $order->payments->where('status', \App\Enums\PaymentStatus::FAILED)->last();
                            @endphp
                            @if ($order->status === \App\Enums\OrderStatus::PENDING_PAYMENT && $failedPayment && ! empty($failedPayment->payload['rejection_reason']))
                                <div class="mt-1.5 p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800">
                                    <strong class="font-bold flex items-center gap-1 text-rose-900">
                                        <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        Bukti Pembayaran Ditolak Admin:
                                    </strong>
                                    <p class="mt-0.5 text-[11px] text-rose-700 font-medium">{{ $failedPayment->payload['rejection_reason'] }}</p>
                                    <span class="text-[10px] text-rose-600 mt-0.5 block">Silakan unggah ulang foto struk transfer yang valid.</span>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-3 flex-wrap">
                            <div class="text-right">
                                <span class="text-[10px] text-slate-400 block uppercase font-bold tracking-wider">Total Tagihan</span>
                                <span class="text-base font-black text-slate-900">
                                    Rp {{ number_format($order->total, 0, ',', '.') }}
                                </span>
                            </div>

                            @php
                                $pendingPayment = $order->payments->where('status', \App\Enums\PaymentStatus::PENDING)->last();
                            @endphp

                            @if (in_array($order->status, [\App\Enums\OrderStatus::PENDING_PAYMENT, \App\Enums\OrderStatus::MENUNGGU_VERIFIKASI]))
                                @if ($pendingPayment)
                                    <button
                                        type="button"
                                        wire:click="openPaymentModal({{ $order->id }})"
                                        class="text-xs px-3.5 py-2 rounded-xl bg-amber-50 text-amber-800 border border-amber-300 font-bold hover:bg-amber-100 transition flex items-center gap-1.5 shadow-xs"
                                    >
                                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Bukti Terkirim (Ubah)</span>
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        wire:click="openPaymentModal({{ $order->id }})"
                                        class="text-xs px-4 py-2 rounded-xl bg-gradient-to-r from-stoqa-600 to-stoqa-500 hover:from-stoqa-700 hover:to-stoqa-600 text-white font-extrabold transition shadow-sm hover:shadow flex items-center gap-1.5 transform active:scale-95"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        <span>Bayar Sekarang</span>
                                    </button>
                                @endif
                            @endif

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

    <!-- Modal Pembayaran & Unggah Bukti Transfer Manual -->
    @if ($showPaymentModal && $payingOrder)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" wire:click="closePaymentModal"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-slate-200">
                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-stoqa-800 to-stoqa-700 px-6 py-5 text-white flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-sand-500 uppercase tracking-wider block">Instruksi Pembayaran</span>
                            <h3 class="text-lg font-black text-white" id="modal-title">
                                {{ $payingOrder->invoice_no ?? ('Order #' . $payingOrder->id) }}
                            </h3>
                        </div>
                        <button type="button" wire:click="closePaymentModal" class="rounded-xl p-1.5 text-white/80 hover:bg-white/10 hover:text-white transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-6 max-h-[80vh] overflow-y-auto">
                        <!-- Rincian Total Tagihan -->
                        <div class="p-4 rounded-2xl bg-stoqa-50/70 border border-stoqa-200/80 flex items-center justify-between">
                            <div>
                                <span class="text-xs text-slate-500 block font-semibold">Total yang Wajib Ditransfer:</span>
                                <div class="text-2xl font-black text-stoqa-800">
                                    Rp {{ number_format($payingOrder->total, 0, ',', '.') }}
                                </div>
                                <span class="text-[11px] text-slate-400">
                                    (Subtotal: Rp {{ number_format($payingOrder->subtotal, 0, ',', '.') }} + Ongkir: Rp {{ number_format($payingOrder->shipping_cost, 0, ',', '.') }})
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 border border-amber-200 block">
                                    Tenggat 12 Jam
                                </span>
                                @if ($payingOrder->expires_at)
                                    <span class="text-[10px] text-slate-400 mt-1 block">
                                        s.d {{ $payingOrder->expires_at->format('H:i') }} WIB
                                    </span>
                                @endif
                            </div>
                        </div>

                        @php
                            $activeMethods = $this->getActivePaymentMethods();
                            $selectedDetails = $activeMethods[$paymentMethod] ?? (reset($activeMethods) ?: null);
                        @endphp

                        <!-- Pilihan Metode Transfer Bank Manual -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih Rekening Tujuan Transfer</label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                @forelse ($activeMethods as $key => $method)
                                    <label class="cursor-pointer border rounded-2xl p-3 flex flex-col justify-between transition {{ $paymentMethod === $key ? 'border-stoqa-600 bg-stoqa-50/60 shadow-xs' : 'border-slate-200 hover:border-slate-300' }}">
                                        <input type="radio" wire:model.live="paymentMethod" value="{{ $key }}" class="sr-only">
                                        <div class="flex items-center justify-between">
                                            <span class="font-extrabold text-sm text-slate-800">{{ $method['name'] }}</span>
                                            <div class="w-3.5 h-3.5 rounded-full border-2 {{ $paymentMethod === $key ? 'border-stoqa-600 bg-stoqa-600' : 'border-slate-300' }}"></div>
                                        </div>
                                        <span class="text-[11px] text-slate-500 mt-2 font-mono">{{ $method['account'] }}</span>
                                    </label>
                                @empty
                                    <p class="text-xs text-amber-700 col-span-3">Metode transfer sedang dipersiapkan oleh admin.</p>
                                @endforelse
                            </div>

                            <!-- Detail Rekening yang Dipilih -->
                            @if ($selectedDetails)
                                <div class="mt-3 p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs" x-data="{ copied: false }">
                                    <div>
                                        <span class="text-slate-400 font-medium">Atas Nama:</span>
                                        <strong class="text-slate-800 block text-sm font-bold">{{ $selectedDetails['holder'] }}</strong>
                                        <span class="text-slate-400 font-medium mt-1 inline-block">Nomor Rekening / Pembayaran:</span>
                                        <span class="font-mono font-black text-sm text-slate-900 block">{{ $selectedDetails['account'] }}</span>
                                    </div>
                                    <div>
                                        <button
                                            type="button"
                                            @click="
                                                navigator.clipboard.writeText('{{ $selectedDetails['account'] }}');
                                                copied = true;
                                                setTimeout(() => copied = false, 2500);
                                            "
                                            class="px-4 py-2 rounded-xl bg-white border border-slate-300 hover:border-stoqa-500 text-slate-700 font-bold transition flex items-center gap-1.5 shadow-xs"
                                        >
                                            <svg x-show="!copied" class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                            <svg x-show="copied" x-cloak class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            <span x-text="copied ? 'Tersalin!' : 'Salin No. Rekening'"></span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Form Bukti Pembayaran -->
                        <form wire:submit="submitPayment" class="space-y-4 pt-2 border-t border-slate-100">
                            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Konfirmasi & Unggah Bukti Struk Transfer</h4>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Bank Asal Anda <span class="text-rose-500">*</span></label>
                                    <input
                                        type="text"
                                        wire:model="senderBank"
                                        placeholder="Contoh: BCA, Mandiri, BRI, SeaBank"
                                        class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-stoqa-500 focus:ring-2 focus:ring-stoqa-100 outline-none"
                                    >
                                    @error('senderBank') <span class="text-rose-600 text-[11px]">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Pemilik Rekening <span class="text-rose-500">*</span></label>
                                    <input
                                        type="text"
                                        wire:model="senderName"
                                        placeholder="Nama yang tertera di rekening pengirim"
                                        class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-stoqa-500 focus:ring-2 focus:ring-stoqa-100 outline-none"
                                    >
                                    @error('senderName') <span class="text-rose-600 text-[11px]">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Foto Bukti / Struk Transfer (Maks. 3MB) <span class="text-rose-500">*</span></label>
                                <input
                                    type="file"
                                    wire:model="proofFile"
                                    accept="image/*"
                                    class="w-full text-xs file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-stoqa-50 file:text-stoqa-700 hover:file:bg-stoqa-100 cursor-pointer border border-slate-300 rounded-xl p-1"
                                >
                                @error('proofFile') <span class="text-rose-600 text-[11px] block mt-1">{{ $message }}</span> @enderror

                                <!-- Preview Gambar -->
                                @if ($proofFile)
                                    <div class="mt-2 p-2 rounded-xl border border-slate-200 bg-slate-50 inline-block">
                                        <span class="text-[10px] text-slate-500 block mb-1 font-semibold">Preview Bukti:</span>
                                        <img src="{{ $proofFile->temporaryUrl() }}" class="h-28 w-auto rounded-lg object-contain">
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                                <input
                                    type="text"
                                    wire:model="paymentNotes"
                                    placeholder="Contoh: Transfer via m-banking jam 14:30"
                                    class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-300 focus:border-stoqa-500 focus:ring-2 focus:ring-stoqa-100 outline-none"
                                >
                            </div>

                            <div class="pt-3 flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    wire:click="closePaymentModal"
                                    class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-stoqa-600 to-stoqa-500 hover:from-stoqa-700 hover:to-stoqa-600 text-white text-xs font-extrabold shadow-sm hover:shadow transition flex items-center gap-2"
                                >
                                    <span wire:loading.remove>Kirim Bukti Pembayaran</span>
                                    <span wire:loading>Mengunggah...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
