@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto my-8 sm:my-12">
    <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Masuk ke Akun</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Masuk untuk melihat harga khusus dan riwayat pesanan Anda.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                    placeholder="nama@email.com"
                >
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-slate-700">Kata Sandi</label>
                </div>
                <input
                    type="password"
                    name="password"
                    required
                    class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                    placeholder="••••••••"
                >
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Ingat saya</span>
                </label>
            </div>

            <button
                type="submit"
                class="w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm tracking-wide transition shadow-lg shadow-blue-500/20"
            >
                Masuk
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-500">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-bold text-blue-600 hover:underline">
                Daftar sekarang
            </a>
        </div>
    </div>
</div>
@endsection
