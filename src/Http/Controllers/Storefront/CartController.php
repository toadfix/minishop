<?php

namespace Minishop\Http\Controllers\Storefront;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Minishop\Http\Controllers\Concerns\ManagesCartItems;
use Minishop\Http\Controllers\Controller;
use Minishop\Http\Resources\CartResource;
use Minishop\Models\Cart;

class CartController extends Controller
{
    use ManagesCartItems;

    public function show(Request $request): Response
    {
        $cart = Cart::resolveOrCreate($request);
        $cart->load(['items.product.images', 'items.variant']);

        return Inertia::render('storefront/Cart', [
            'cart' => new CartResource($cart),
        ]);
    }
}
