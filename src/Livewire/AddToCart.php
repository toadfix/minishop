<?php

namespace Minishop\Livewire;

use Livewire\Attributes\Locked;
use Livewire\Component;
use Minishop\Models\Cart;
use Minishop\Models\Product;
use Minishop\Models\ProductVariant;

class AddToCart extends Component
{
    #[Locked]
    public int $productId;

    public ?int $variantId = null;

    public int $quantity = 1;

    public bool $added = false;

    public function mount(Product $product): void
    {
        $this->productId = $product->id;

        // Default to the first active variant for variable products.
        if ($product->isVariable()) {
            $this->variantId = $product->variants->firstWhere('is_active', true)?->id;
        }
    }

    public function add(): void
    {
        $this->validate([
            'quantity' => ['integer', 'min:1', 'max:99'],
        ]);

        $product = Product::query()->findOrFail($this->productId);

        if (! $product->is_active) {
            $this->addError('quantity', 'This product is no longer available.');

            return;
        }

        $unitPrice = $product->price;
        $variantId = null;

        if ($this->variantId) {
            $variant = ProductVariant::query()
                ->where('id', $this->variantId)
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->first();

            if (! $variant) {
                $this->addError('variantId', 'Please choose an available option.');

                return;
            }

            $variantId = $variant->id;
            $unitPrice = $variant->price ?? $product->price;
        } elseif ($product->isVariable()) {
            $this->addError('variantId', 'Please choose an option.');

            return;
        }

        $cart = Cart::resolveOrCreate(request());

        $existing = $cart->items()
            ->where('product_id', $product->id)
            ->when($variantId === null, fn ($q) => $q->whereNull('variant_id'), fn ($q) => $q->where('variant_id', $variantId))
            ->first();

        if ($existing) {
            $existing->increment('quantity', $this->quantity);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'quantity' => $this->quantity,
                'unit_price' => $unitPrice,
            ]);
        }

        $this->added = true;
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        $product = Product::query()->with('variants.optionValues.option')->findOrFail($this->productId);

        return view('minishop::livewire.storefront.add-to-cart', [
            'product' => $product,
            'variants' => $product->isVariable() ? $product->variants->where('is_active', true) : collect(),
        ]);
    }
}
