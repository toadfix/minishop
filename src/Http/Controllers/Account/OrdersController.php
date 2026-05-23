<?php

namespace Minishop\Http\Controllers\Account;

use Illuminate\Http\Request;
use Minishop\Http\Controllers\Controller;
use Minishop\Models\Order;
use Minishop\Rendering\StorefrontRendererContract;

class OrdersController extends Controller
{
    public function __construct(private StorefrontRendererContract $renderer) {}

    public function index(Request $request): mixed
    {
        $orders = $request->user()->customer
            ->orders()
            ->with(['items.product', 'shippingMethod'])
            ->latest()
            ->paginate(10);

        return $this->renderer->render('storefront/Account/Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, Order $order): mixed
    {
        abort_unless(
            $order->customer_id === $request->user()->customer?->id,
            403,
        );

        $order->load(['items.product', 'items.variant', 'shippingMethod', 'coupon']);

        return $this->renderer->render('storefront/Account/Orders/Show', [
            'order' => $order,
        ]);
    }
}
