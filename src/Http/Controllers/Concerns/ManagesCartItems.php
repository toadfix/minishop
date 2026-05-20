<?php

namespace Minishop\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Minishop\Http\Requests\Storefront\AddCartItemRequest;
use Minishop\Http\Requests\Storefront\SyncCartRequest;
use Minishop\Http\Requests\Storefront\UpdateCartItemRequest;
use Minishop\Http\Resources\CartResource;
use Minishop\Models\Cart;
use Minishop\Models\CartItem;
use Minishop\Models\Product;
use Minishop\Models\ProductVariant;

trait ManagesCartItems
{
    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $cart = Cart::resolveOrCreate($request);
        $validated = $request->validated();

        $product = Product::query()->findOrFail($validated['product_id']);
        abort_unless($product->is_active, 422, 'This product is no longer available.');

        if ($product->isBundled() && ! empty($validated['variant_id'])) {
            abort(422, 'Bundled products do not support variants.');
        }

        $unitPrice = $product->price;

        if (! empty($validated['variant_id'])) {
            $variant = ProductVariant::query()
                ->where('id', $validated['variant_id'])
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->firstOrFail();

            $unitPrice = $variant->price ?? $product->price;
        }

        $variantId = $validated['variant_id'] ?? null;

        $existing = $cart->items()
            ->where('product_id', $validated['product_id'])
            ->when($variantId === null, fn ($q) => $q->whereNull('variant_id'), fn ($q) => $q->where('variant_id', $variantId))
            ->first();

        if ($existing) {
            $existing->increment('quantity', $validated['quantity']);
        } else {
            $cart->items()->create([
                'product_id' => $validated['product_id'],
                'variant_id' => $variantId,
                'quantity' => $validated['quantity'],
                'unit_price' => $unitPrice,
            ]);
        }

        $cart->load(['items.product.images', 'items.variant']);

        return response()->json(new CartResource($cart));
    }

    public function updateItem(UpdateCartItemRequest $request, CartItem $cartItem): JsonResponse
    {
        $this->authorizeCartItem($request, $cartItem);

        $cart = $cartItem->cart;
        $quantity = $request->validated()['quantity'];

        if ($quantity === 0) {
            $cartItem->delete();
        } else {
            $cartItem->update(['quantity' => $quantity]);
        }

        $cart->load(['items.product.images', 'items.variant']);

        return response()->json(new CartResource($cart));
    }

    public function removeItem(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->authorizeCartItem($request, $cartItem);

        $cart = $cartItem->cart;
        $cartItem->delete();
        $cart->load(['items.product.images', 'items.variant']);

        return response()->json(new CartResource($cart));
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = Cart::resolveOrCreate($request);
        $cart->items()->delete();
        $cart->setRelation('items', collect());

        return response()->json(new CartResource($cart));
    }

    public function sync(SyncCartRequest $request): JsonResponse
    {
        $cart = Cart::resolveOrCreate($request);

        $productIds = collect($request->validated()['items'])->pluck('product_id')->unique();
        $variantIds = collect($request->validated()['items'])->pluck('variant_id')->filter()->unique();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $variants = $variantIds->isNotEmpty()
            ? ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->where('is_active', true)
                ->get()
                ->keyBy('id')
            : collect();

        foreach ($request->validated()['items'] as $item) {
            $product = $products->get($item['product_id']);

            if (! $product) {
                continue;
            }

            $variantId = $item['variant_id'] ?? null;
            $unitPrice = $product->price;

            if ($variantId) {
                $variant = $variants->get($variantId);

                if (! $variant || $variant->product_id !== $product->id) {
                    continue;
                }

                $unitPrice = $variant->price ?? $product->price;
            }

            $cartItem = $cart->items()
                ->where('product_id', $item['product_id'])
                ->when($variantId === null, fn ($q) => $q->whereNull('variant_id'), fn ($q) => $q->where('variant_id', $variantId))
                ->first();

            if ($cartItem) {
                $cartItem->update(['quantity' => $item['quantity'], 'unit_price' => $unitPrice]);
            } else {
                $cart->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $variantId,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                ]);
            }
        }

        $cart->load(['items.product.images', 'items.variant']);

        return response()->json(new CartResource($cart));
    }

    private function authorizeCartItem(Request $request, CartItem $cartItem): void
    {
        $cart = $cartItem->cart;

        $isOwner = $request->user()
            ? $cart->user_id === $request->user()->id
            : $cart->session_id === $request->cookie('cart_token');

        abort_unless($isOwner, 403);
    }
}
