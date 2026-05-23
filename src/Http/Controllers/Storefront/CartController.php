<?php

namespace Minishop\Http\Controllers\Storefront;

use Illuminate\Http\Request;
use Minishop\Http\Controllers\Concerns\ManagesCartItems;
use Minishop\Http\Controllers\Controller;
use Minishop\Http\Resources\CartResource;
use Minishop\Models\Cart;
use Minishop\Rendering\StorefrontRendererContract;

class CartController extends Controller
{
    use ManagesCartItems;

    public function __construct(private StorefrontRendererContract $renderer) {}

    public function show(Request $request): mixed
    {
        $cart = Cart::resolveOrCreate($request);
        $cart->load(['items.product.images', 'items.variant']);

        return $this->renderer->render('storefront/Cart', [
            'cart' => new CartResource($cart),
        ]);
    }
}
