<div class="max-w-xl mx-auto py-4" x-data="{ showLogoutModal: false }">
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Akun Saya</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola data profil, pesanan, dan sesi login Anda.</p>
    </div>

    @if (! $user)
        <!-- Keadaan Belum Login -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 text-center shadow-sm">
            <div class="w-16 h-16 mx-auto mb-4 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <h2 class="text-lg font-bold text-slate-900 mb-1">Anda Belum Masuk</h2>
            <p class="text-xs text-slate-500 mb-6">Masuk untuk melihat pesanan Anda, harga khusus pelanggan langganan, dan checkout lebih cepat.</p>

            <div class="space-y-3">
                <a
                    href="{{ route('login') }}"
                    wire:navigate
                    class="w-full block py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-sm transition"
                >
                    Masuk ke Akun
                </a>
                <a
                    href="{{ route('register') }}"
                    wire:navigate
                    class="w-full block py-3 px-4 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-sm transition"
                >
                    Daftar Akun Baru
                </a>
            </div>
        </div>
    @else
        <!-- Keadaan Sudah Login -->
        <div class="space-y-4">
            <!-- Kartu Info Profil -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-extrabold text-xl flex items-center justify-center shadow-sm">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="flex-grow">
                        <div class="flex items-center gap-2">
                            <h2 class="text-base sm:text-lg font-extrabold text-slate-900 leading-tight">{{ $user->name }}</h2>
                            @if ($user->hasRole('admin'))
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Admin</span>
                            @elseif ($user->hasRole('staf_gudang'))
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800">Staf Gudang</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700">Pelanggan</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $user->email }}</p>
                        @if ($user->phone)
                            <p class="text-xs text-slate-500">{{ $user->phone }}</p>
                        @endif
                    </div>
                </div>

                @if ($user->address)
                    <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-600">
                        <span class="font-bold text-slate-700 block mb-0.5">Alamat Pengiriman Utama:</span>
                        <p class="text-slate-500 leading-relaxed">{{ $user->address }}</p>
                    </div>
                @endif
            </div>

            <!-- Navigasi Menu Akun -->
            <div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100 shadow-sm overflow-hidden">
                <a
                    href="{{ url('/pesanan-saya') }}"
                    wire:navigate
                    class="p-4 flex items-center justify-between hover:bg-slate-50 transition"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <div>
                            <span class="text-sm font-bold text-slate-800 block">Pesanan Saya</span>
                            <span class="text-[11px] text-slate-400">Pantau status ongkir & pembayaran</span>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>

                <a
                    href="{{ url('/keranjang') }}"
                    wire:navigate
                    class="p-4 flex items-center justify-between hover:bg-slate-50 transition"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </div>
                        <div>
                            <span class="text-sm font-bold text-slate-800 block">Keranjang Belanja</span>
                            <span class="text-[11px] text-slate-400">{{ app(\App\Services\CartService::class)->count() }} Barang di keranjang</span>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>

                @role('admin|staf_gudang')
                    <a
                        href="{{ url('/admin') }}"
                        class="p-4 flex items-center justify-between hover:bg-amber-50/50 transition bg-amber-50/20"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div>
                                <span class="text-sm font-bold text-amber-900 block">Buka Panel Admin Gudang</span>
                                <span class="text-[11px] text-amber-700">Kelola stok, order, & barang masuk</span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endrole
            </div>

            <!-- Tombol Keluar (Logout) -->
            <div class="pt-2">
                <button
                    type="button"
                    @click="showLogoutModal = true"
                    class="w-full py-3.5 px-4 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-sm border border-rose-200 transition flex items-center justify-center gap-2 active:scale-[0.99] cursor-pointer"
                >
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>Keluar dari Akun</span>
                </button>
            </div>

            <!-- Modal Konfirmasi Keluar Kustom (Bukan Browser Alert) -->
            <div
                x-show="showLogoutModal"
                x-cloak
                class="fixed inset-0 z-50 overflow-y-auto"
                aria-labelledby="modal-title"
                role="dialog"
                aria-modal="true"
            >
                <div class="flex items-center justify-center min-h-screen px-4 py-6 text-center">
                    <!-- Backdrop -->
                    <div
                        x-show="showLogoutModal"
                        x-transition:enter="ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        @click="showLogoutModal = false"
                        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                        aria-hidden="true"
                    ></div>

                    <!-- Card Dialog -->
                    <div
                        x-show="showLogoutModal"
                        x-transition:enter="ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="relative inline-block w-full max-w-sm p-6 overflow-hidden text-center bg-white rounded-3xl shadow-2xl border border-slate-100 transform transition-all z-10"
                    >
                        <!-- Icon Pintu / Keluar Merah Lembut -->
                        <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-2xl bg-rose-50 border border-rose-100 text-rose-600 mb-4 shadow-xs">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </div>

                        <h3 class="text-base font-extrabold text-slate-900" id="modal-title">
                            Konfirmasi Keluar Akun
                        </h3>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Apakah Anda yakin ingin keluar dari akun Stoqa ini? Anda dapat masuk kembali kapan saja.
                        </p>

                        <!-- Pilihan Tombol -->
                        <div class="mt-6 flex items-center gap-3">
                            <button
                                type="button"
                                @click="showLogoutModal = false"
                                class="flex-1 py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                wire:click="logout"
                                wire:loading.attr="disabled"
                                class="flex-1 py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-sm hover:shadow transition flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                            >
                                <span wire:loading.remove>Ya, Keluar</span>
                                <span wire:loading>Memproses...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
