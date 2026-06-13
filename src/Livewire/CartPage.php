<?php

namespace Minishop\Livewire;

use Livewire\Component;
use Minishop\Models\Cart;
use Minishop\Models\CartItem;

class CartPage extends Component
{
    public function updateQuantity(int $itemId, int $quantity): void
    {
        $item = $this->ownedItem($itemId);

        if (! $item) {
            return;
        }

        if ($quantity < 1) {
            $item->delete();
        } else {
            $item->update(['quantity' => min($quantity, 99)]);
        }

        $this->dispatch('cart-updated');
    }

    public function remove(int $itemId): void
    {
        $this->ownedItem($itemId)?->delete();
        $this->dispatch('cart-updated');
    }

    public function clear(): void
    {
        Cart::resolveOrCreate(request())->items()->delete();
        $this->dispatch('cart-updated');
    }

    /**
     * Load a cart item only if it belongs to the current visitor's cart.
     */
    protected function ownedItem(int $itemId): ?CartItem
    {
        $cart = Cart::resolveOrCreate(request());

        return $cart->items()->whereKey($itemId)->first();
    }

    public function render()
    {
        $cart = Cart::resolveOrCreate(request());
        $cart->load(['items.product.images', 'items.variant.optionValues']);

        return view('minishop::livewire.storefront.cart-page', [
            'cart' => $cart,
            'subtotal' => $cart->items->sum(fn ($i) => $i->unit_price * $i->quantity),
        ]);
    }
}
