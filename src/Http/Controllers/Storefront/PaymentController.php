<?php

namespace Minishop\Http\Controllers\Storefront;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Minishop\Http\Controllers\Controller;
use Minishop\Models\Order;
use Minishop\Payments\Facades\Payment;
use Minishop\Rendering\StorefrontRendererContract;

class PaymentController extends Controller
{
    public function __construct(private StorefrontRendererContract $renderer) {}

    public function show(Order $order, Request $request): mixed
    {
        $this->authorizeOrderAccess($order, $request);

        if ($order->payment_status === 'paid') {
            return redirect()->route('storefront.order.confirmation', $order);
        }

        $order->load(['items', 'customer.user', 'shippingMethod']);

        return $this->renderer->render('storefront/Payment', [
            'order' => $order,
        ]);
    }

    public function stripeIntent(Order $order, Request $request): JsonResponse
    {
        $this->authorizeOrderAccess($order, $request);

        abort_if($order->payment_status === 'paid', 422, 'Order is already paid.');

        return Payment::driver('stripe')->initiate($order, $request);
    }

    private function authorizeOrderAccess(Order $order, Request $request): void
    {
        $ownedBySession = $request->session()->get('checkout_order_id') === $order->id;
        $ownedByUser = auth()->check() && $order->customer?->user_id === auth()->id();

        abort_unless($ownedBySession || $ownedByUser, 403);
    }
}
