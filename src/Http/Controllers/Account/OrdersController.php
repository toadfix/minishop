<?php

namespace Minishop\Http\Controllers\Account;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Minishop\Http\Controllers\Controller;
use Minishop\Models\Order;

class OrdersController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = $request->user()->customer
            ->orders()
            ->with(['items.product', 'shippingMethod'])
            ->latest()
            ->paginate(10);

        return Inertia::render('storefront/Account/Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        abort_unless(
            $order->customer_id === $request->user()->customer?->id,
            403,
        );

        $order->load(['items.product', 'items.variant', 'shippingMethod', 'coupon']);

        return Inertia::render('storefront/Account/Orders/Show', [
            'order' => $order,
        ]);
    }
}
