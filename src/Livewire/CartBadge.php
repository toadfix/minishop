<?php

namespace Minishop\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use Minishop\Models\Cart;

class CartBadge extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->refreshCount();
    }

    #[On('cart-updated')]
    public function refreshCount(): void
    {
        $cart = Cart::resolveOrCreate(request());
        $this->count = (int) $cart->items()->sum('quantity');
    }

    public function render()
    {
        return view('minishop::livewire.storefront.cart-badge');
    }
}
