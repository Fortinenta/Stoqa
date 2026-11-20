@php
    $latestPayment = $order->relationLoaded('payments')
        ? $order->payments->whereNotNull('proof_url')->last() ?? $order->payments->last()
        : $order->payments()->latest()->first();
    $payload = $latestPayment?->payload ?? [];
@endphp

<div class="space-y-4 text-xs">
    <!-- Ringkasan Order & Total -->
    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <div>
            <span class="text-slate-400 font-semibold block text-[11px]">Total Tagihan Pesanan:</span>
            <span class="text-xl font-black text-slate-900 dark:text-white">
                Rp {{ number_format($order->total, 0, ',', '.') }}
            </span>
            <span class="text-[10px] text-slate-400 block mt-0.5">
                (Subtotal: Rp {{ number_format($order->subtotal, 0, ',', '.') }} + Ongkir: Rp {{ number_format($order->shipping_cost, 0, ',', '.') }})
            </span>
        </div>
        <div class="text-right">
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">
                {{ $order->invoice_no ?? ('Order #' . $order->id) }}
            </span>
        </div>
    </div>

    <!-- Informasi Bukti Transfer Pembeli -->
    @if ($latestPayment && $latestPayment->proof_url)
        <div class="p-4 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/50 dark:bg-emerald-950/20 space-y-3">
            <div class="flex items-center justify-between">
                <span class="font-bold text-emerald-800 dark:text-emerald-400 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Bukti Transfer Telah Diunggah Pembeli
                </span>
                <span class="text-[10px] text-slate-400 font-mono">
                    {{ $latestPayment->created_at->format('d/m/Y H:i') }} WIB
                </span>
            </div>

            <div class="grid grid-cols-2 gap-2 text-[11px] pt-1 border-t border-emerald-100 dark:border-emerald-800/40">
                <div>
                    <span class="text-slate-500 block">Bank Asal:</span>
                    <strong class="text-slate-800 dark:text-slate-200">{{ $payload['sender_bank'] ?? 'Transfer Manual' }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 block">Nama Rekening:</span>
                    <strong class="text-slate-800 dark:text-slate-200">{{ $payload['sender_name'] ?? $order->customer_name }}</strong>
                </div>
                @if (!empty($payload['notes']))
                    <div class="col-span-2">
                        <span class="text-slate-500 block">Catatan Pembeli:</span>
                        <span class="italic text-slate-700 dark:text-slate-300">{{ $payload['notes'] }}</span>
                    </div>
                @endif
            </div>

            <!-- Preview Gambar Bukti Struk -->
            <div class="pt-2">
                <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 block mb-1.5">Foto Struk / Tangkapan Layar:</span>
                <a href="{{ route('payment.proof.show', ['order' => $order->id, 'payment' => $latestPayment->id]) }}" target="_blank" class="block group relative rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 max-w-xs hover:border-blue-500 transition">
                    <img src="{{ route('payment.proof.show', ['order' => $order->id, 'payment' => $latestPayment->id]) }}" alt="Bukti Transfer" class="w-full max-h-56 object-contain bg-slate-100 dark:bg-slate-900">
                    <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white font-bold text-xs">
                        Buka Gambar Penuh ↗
                    </div>
                </a>
            </div>
        </div>
    @else
        <div class="p-3.5 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/50 dark:bg-amber-950/20 text-amber-800 dark:text-amber-400 text-xs">
            <div class="flex items-start gap-2">
                <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <strong class="font-bold block">Pembeli Belum Mengunggah Bukti Transfer di Website</strong>
                    <span>Jika pembeli membayar tunai di kasir atau telah mengirim bukti via WhatsApp, pastikan mutasi dana telah masuk sebelum mengonfirmasi.</span>
                </div>
            </div>
        </div>
    @endif

    @if (! ($isViewOnly ?? false))
        <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-[11px] text-slate-500">
            Konfirmasi ini akan memotong saldo fisik batch FIFO secara permanen dan mencatat HPP serta laba kotor pada laporan keuangan.
        </div>
    @endif
</div>
