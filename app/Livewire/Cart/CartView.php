<?php

namespace App\Livewire\Cart;

use App\Services\CartService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CartView extends Component
{
    public function removeItem(string $key, CartService $cartService): void
    {
        $cartService->remove($key);
        session()->flash('success', 'Item berhasil dihapus dari keranjang.');
    }

    public function clearCart(CartService $cartService): void
    {
        $cartService->clear();
        session()->flash('success', 'Keranjang belanja telah dikosongkan.');
    }

    public function render(CartService $cartService)
    {
        $cartData = $cartService->get(auth()->user());

        return view('livewire.cart.cart-view', [
            'cart' => $cartData,
        ]);
    }
}
