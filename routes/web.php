<?php

use App\Http\Controllers\AuthController;
use App\Livewire\Account\OrderHistory;
use App\Livewire\Account\ProfileView;
use App\Livewire\Cart\CartView;
use App\Livewire\Catalog\ProductDetail;
use App\Livewire\Catalog\ProductList;
use App\Livewire\Checkout\CheckoutView;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Stoqa (Katalog, Keranjang, Checkout, & Pesanan)
|--------------------------------------------------------------------------
*/

// Katalog & Produk Publik
Route::get('/', ProductList::class)->name('home');
Route::get('/produk/{sku}', ProductDetail::class)->name('catalog.product');

// Keranjang Belanja
Route::get('/keranjang', CartView::class)->name('cart');

// Halaman Profil & Akun (Mobile & Desktop)
Route::get('/akun', ProfileView::class)->name('account.profile');

// Authentication Pelanggan
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Area Pelanggan Terautentikasi
Route::middleware('auth')->group(function () {
    Route::get('/checkout', CheckoutView::class)->name('checkout');
    Route::get('/pesanan-saya', OrderHistory::class)->name('account.orders');
});
