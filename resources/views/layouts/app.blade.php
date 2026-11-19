<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Katalog & Stok Gudang' }} - Stoqa</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/stoqa-icon.svg') }}">

    <!-- Compiled Assets (Fast & Lightweight) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
    </style>

    @livewireStyles
</head>
<body class="flex flex-col min-h-screen text-slate-800 antialiased font-sans pb-16 md:pb-0" style="background: radial-gradient(1100px 480px at -5% -12%, rgba(130, 211, 201, 0.20), transparent 60%), radial-gradient(800px 380px at 105% -5%, rgba(244, 184, 96, 0.12), transparent 55%), #F6F8F7; background-attachment: fixed;">

    <!-- Header & Navigation -->
    <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-slate-200/80 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo & Brand -->
                <div class="flex items-center gap-3">
                    <a href="{{ url('/') }}" wire:navigate class="flex items-center gap-2 group transition transform hover:scale-[1.02]">
                        <img src="{{ asset('images/stoqa-logo.svg') }}" alt="Stoqa Logo" class="h-8 w-auto">
                    </a>
                </div>

                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center gap-6">
                    <a href="{{ url('/') }}" wire:navigate class="text-sm font-semibold {{ request()->is('/') ? 'text-stoqa-600 font-extrabold border-b-2 border-stoqa-600 pb-0.5' : 'text-slate-600 hover:text-stoqa-600 transition' }}">
                        Katalog Produk
                    </a>
                    <a href="{{ url('/keranjang') }}" wire:navigate class="text-sm font-semibold {{ request()->is('keranjang') ? 'text-stoqa-600 font-extrabold border-b-2 border-stoqa-600 pb-0.5' : 'text-slate-600 hover:text-stoqa-600 transition' }} flex items-center gap-1.5">
                        <span>Keranjang</span>
                        <span class="px-2 py-0.5 text-xs rounded-full bg-stoqa-100 text-stoqa-700 font-bold border border-stoqa-200">
                            {{ app(\App\Services\CartService::class)->count() }}
                        </span>
                    </a>
                    @auth
                        <a href="{{ url('/pesanan-saya') }}" wire:navigate class="text-sm font-semibold {{ request()->is('pesanan-saya*') ? 'text-stoqa-600 font-extrabold border-b-2 border-stoqa-600 pb-0.5' : 'text-slate-600 hover:text-stoqa-600 transition' }}">
                            Pesanan Saya
                        </a>
                        @role('admin|staf_gudang')
                            <a href="{{ url('/admin') }}" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-sand-100 text-sand-800 border border-sand-500/30 hover:bg-sand-500/20 transition flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>Panel Manajemen</span>
                            </a>
                        @endrole
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-rose-600 hover:text-rose-700 transition">
                                Keluar
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700 hover:text-stoqa-600 transition">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" class="text-sm font-bold px-4 py-2 rounded-xl bg-stoqa-600 hover:bg-stoqa-700 text-white shadow-sm hover:shadow transition transform active:scale-95">
                            Daftar Akun
                        </a>
                    @endauth
                </nav>

                <!-- Mobile Header Right -->
                <div class="flex items-center md:hidden gap-2">
                    <a href="{{ url('/keranjang') }}" wire:navigate class="relative p-2 text-slate-700 hover:text-stoqa-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span class="absolute top-1 right-1 w-4 h-4 bg-stoqa-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                            {{ app(\App\Services\CartService::class)->count() }}
                        </span>
                    </a>

                    @auth
                        <a href="{{ url('/akun') }}" wire:navigate class="w-8 h-8 rounded-full bg-stoqa-600 text-white font-bold text-xs flex items-center justify-center shadow-xs">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" wire:navigate class="px-2.5 py-1 text-xs font-bold text-stoqa-600 border border-stoqa-200 rounded-lg hover:bg-stoqa-50 transition">
                            Masuk
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Notifications -->
    @if (session()->has('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between gap-3 shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <a href="{{ url('/keranjang') }}" wire:navigate class="shrink-0 text-xs font-bold text-emerald-900 bg-emerald-100/80 hover:bg-emerald-200 px-3 py-1.5 rounded-lg border border-emerald-300 transition flex items-center gap-1">
                    <span>Lihat Keranjang</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="mt-auto border-t border-slate-200/80 bg-white/60 backdrop-blur-md py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/stoqa-icon.svg') }}" alt="Stoqa Icon" class="h-5 w-auto">
                    <span class="font-bold text-slate-700">Stoqa</span>
                    <span>&copy; {{ date('Y') }} Platform Kulakan & Inventaris Multi-Satuan.</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center gap-1.5 text-stoqa-700 font-semibold">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        Stok FIFO Akurat Real-time
                    </span>
                    <span>•</span>
                    <span>Batas Ongkir 3 Jam</span>
                    <span>•</span>
                    <span>Batas Bayar 12 Jam</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Mobile Bottom Navigation Bar (4 Menu Responsif) -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 z-50 flex items-center justify-around h-15 pb-safe">
        <a href="{{ url('/') }}" wire:navigate class="flex flex-col items-center justify-center flex-1 py-1.5 text-[11px] {{ request()->is('/') ? 'text-stoqa-600 font-bold' : 'text-slate-500 hover:text-stoqa-600' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span>Katalog</span>
        </a>

        <a href="{{ url('/keranjang') }}" wire:navigate class="flex flex-col items-center justify-center flex-1 py-1.5 text-[11px] relative {{ request()->is('keranjang') ? 'text-stoqa-600 font-bold' : 'text-slate-500 hover:text-stoqa-600' }}">
            <div class="relative">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                @if (app(\App\Services\CartService::class)->count() > 0)
                    <span class="absolute -top-1 -right-2 w-3.5 h-3.5 bg-stoqa-600 text-white text-[9px] font-bold rounded-full flex items-center justify-center">
                        {{ app(\App\Services\CartService::class)->count() }}
                    </span>
                @endif
            </div>
            <span>Keranjang</span>
        </a>

        <a href="{{ auth()->check() ? url('/pesanan-saya') : route('login') }}" wire:navigate class="flex flex-col items-center justify-center flex-1 py-1.5 text-[11px] {{ request()->is('pesanan-saya*') ? 'text-stoqa-600 font-bold' : 'text-slate-500 hover:text-stoqa-600' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span>Pesanan</span>
        </a>

        <a href="{{ url('/akun') }}" wire:navigate class="flex flex-col items-center justify-center flex-1 py-1.5 text-[11px] {{ request()->is('akun*') || request()->is('login') || request()->is('register') ? 'text-stoqa-600 font-bold' : 'text-slate-500 hover:text-stoqa-600' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>{{ auth()->check() ? 'Akun' : 'Masuk' }}</span>
        </a>
    </div>

    @livewireScripts
</body>
</html>
