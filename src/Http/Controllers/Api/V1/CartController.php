<?php

namespace Minishop\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Minishop\Http\Controllers\Concerns\ManagesCartItems;
use Minishop\Http\Resources\CartResource;
use Minishop\Models\Cart;

class CartController extends Controller
{
    use ManagesCartItems;

    public function show(Request $request): JsonResponse
    {
        $cart = Cart::resolveOrCreate($request);
        $cart->load(['items.product.images', 'items.variant']);

        return response()->json(new CartResource($cart));
    }
}
