@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto my-8 sm:my-12">
    <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Daftar Akun Baru</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Buat akun untuk memesan dengan tier harga grosir terbaik.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap / Nama Toko <span class="text-rose-500">*</span></label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                    placeholder="Contoh: Toko Berkah / Budi"
                >
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email <span class="text-rose-500">*</span></label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                    placeholder="nama@email.com"
                >
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp / HP Aktif <span class="text-rose-500">*</span></label>
                <input
                    type="tel"
                    name="phone"
                    value="{{ old('phone') }}"
                    required
                    class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                    placeholder="081234567890"
                >
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi (Min. 6 Karakter) <span class="text-rose-500">*</span></label>
                <input
                    type="password"
                    name="password"
                    required
                    class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                    placeholder="••••••••"
                >
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Konfirmasi Kata Sandi <span class="text-rose-500">*</span></label>
                <input
                    type="password"
                    name="password_confirmation"
                    required
                    class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                    placeholder="••••••••"
                >
            </div>

            <button
                type="submit"
                class="w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm tracking-wide transition shadow-lg shadow-blue-500/20"
            >
                Daftar Akun
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-500">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-bold text-blue-600 hover:underline">
                Masuk di sini
            </a>
        </div>
    </div>
</div>
@endsection
