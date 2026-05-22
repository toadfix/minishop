<?php

namespace Minishop\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Minishop\Http\Controllers\Controller;
use Minishop\Http\Resources\OrderResource;
use Minishop\Models\Order;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $customer = $request->user()->customer;

        if (! $customer) {
            return OrderResource::collection(collect());
        }

        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->with(['items', 'shippingMethod'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('viewOwn', $order);

        $order->load(['items.product', 'items.variant', 'shippingMethod']);

        return response()->json(new OrderResource($order));
    }
}
