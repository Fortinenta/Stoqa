<div>
    <div class="mb-6">
        <a href="{{ url('/keranjang') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Keranjang
        </a>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Checkout Pesanan</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">Lengkapi data tujuan pengiriman untuk mereservasi stok produk Anda.</p>
    </div>

    @if (session()->has('error'))
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Kolom Kiri: Form Informasi Pengiriman -->
        <div class="lg:col-span-7 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Data Penerima & Alamat Pengiriman
                </h2>

                <form wire:submit.prevent="placeOrder" id="checkout-form" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Penerima <span class="text-rose-500">*</span></label>
                        <input
                            type="text"
                            wire:model="customer_name"
                            class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border @error('customer_name') border-rose-300 ring-rose-200 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                            placeholder="Contoh: Budi Santoso"
                        >
                        @error('customer_name') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp / HP Aktif <span class="text-rose-500">*</span></label>
                        <input
                            type="tel"
                            wire:model="phone"
                            class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border @error('phone') border-rose-300 ring-rose-200 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                            placeholder="Contoh: 081234567890"
                        >
                        <p class="text-[10px] text-slate-400 mt-1">Admin akan menghubungi via WhatsApp jika diperlukan konfirmasi ekspedisi.</p>
                        @error('phone') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Lengkap Pengiriman <span class="text-rose-500">*</span></label>
                        <textarea
                            wire:model="shipping_address"
                            rows="3"
                            class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border @error('shipping_address') border-rose-300 ring-rose-200 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                            placeholder="Tuliskan nama jalan, nomor rumah/toko, RT/RW, kelurahan, kecamatan, kota/kabupaten, dan kode pos"
                        ></textarea>
                        @error('shipping_address') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan untuk Admin / Gudang (Opsional)</label>
                        <input
                            type="text"
                            wire:model="note"
                            class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                            placeholder="Contoh: Tolong packing kayu / Kirim via ekspedisi langganan"
                        >
                    </div>
                </form>
            </div>

            <!-- Banner Alur Reservasi & Ongkir -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-2xl p-5">
                <div class="flex items-start gap-3.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center flex-shrink-0 shadow-sm mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="text-xs text-slate-600 space-y-1.5">
                        <h4 class="font-bold text-slate-900 text-sm">Bagaimana alur setelah order dibuat?</h4>
                        <p>1. <strong>Stok Langsung Ditahan (HOLD)</strong>: Sistem akan memesan kuota stok produk agar tidak dibeli pelanggan lain.</p>
                        <p>2. <strong>Admin Menghitung Ongkir (Maks. 3 Jam)</strong>: Admin gudang akan menimbang fisik barang dan memasukkan biaya ekspedisi termurah.</p>
                        <p>3. <strong>Pembayaran (12 Jam)</strong>: Setelah nomor invoice dan total ongkir diterbitkan, Anda memiliki waktu 12 jam untuk menyelesaikan pembayaran.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Rincian Pesanan & Tombol Checkout -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm sticky top-24">
                <h2 class="text-base font-bold text-slate-900 mb-4 pb-3 border-b border-slate-100 flex items-center justify-between">
                    <span>Ringkasan Item</span>
                    <span class="text-xs font-normal text-slate-500">{{ count($cart['items'] ?? []) }} Barang</span>
                </h2>

                <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto pr-1 mb-4 space-y-3">
                    @foreach ($cart['items'] as $item)
                        <div class="pt-3 first:pt-0 flex items-start justify-between gap-3">
                            <div class="flex-grow">
                                <h4 class="text-xs font-bold text-slate-800 line-clamp-1">{{ $item['product_name'] }}</h4>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    @foreach ($item['units_breakdown'] as $b)
                                        <span class="inline-block mr-2">{{ $b['qty'] }} {{ $b['unit_name'] }}</span>
                                    @endforeach
                                    <span class="text-slate-400">({{ $item['total_base_qty'] }} Pcs)</span>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="text-xs font-extrabold text-slate-900">
                                    Rp {{ number_format($item['total_price'], 0, ',', '.') }}
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    @ Rp {{ number_format($item['unit_price'], 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="space-y-2 pt-3 border-t border-slate-100 text-xs">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Subtotal Produk</span>
                        <span class="font-bold text-slate-800">Rp {{ number_format($cart['subtotal'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Biaya Pengiriman (Ongkir)</span>
                        <span class="text-amber-600 font-semibold bg-amber-50 px-2 py-0.5 rounded text-[11px]">Dihitung Admin Gudang</span>
                    </div>
                    <div class="pt-3 border-t border-slate-200 flex items-baseline justify-between">
                        <div>
                            <span class="text-sm font-extrabold text-slate-900">Total Sementara</span>
                            <p class="text-[10px] text-slate-400">Belum termasuk ongkir</p>
                        </div>
                        <span class="text-lg font-black text-blue-600">
                            Rp {{ number_format($cart['subtotal'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <div class="mt-6">
                    <button
                        type="submit"
                        form="checkout-form"
                        wire:loading.attr="disabled"
                        class="w-full py-3.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 text-white font-bold text-xs sm:text-sm tracking-wide transition shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2"
                    >
                        <span wire:loading.remove wire:target="placeOrder" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Buat Pesanan & Tahan Stok
                        </span>
                        <span wire:loading wire:target="placeOrder" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            Memproses & Mengunci Stok...
                        </span>
                    </button>
                    <p class="text-[10px] text-center text-slate-400 mt-2">
                        Dengan membuat pesanan, kuota produk Anda dijamin aman.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
