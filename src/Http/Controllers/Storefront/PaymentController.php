<?php

namespace Minishop\Http\Controllers\Storefront;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Minishop\Enums\OrderStatus;
use Minishop\Http\Controllers\Controller;
use Minishop\Models\Order;
use Minishop\Models\StoreSettings;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function show(Order $order): Response|RedirectResponse
    {
        if ($order->payment_status === 'paid') {
            return redirect()->route('storefront.order.confirmation', $order);
        }

        $order->load(['items', 'customer.user', 'shippingMethod']);

        return Inertia::render('storefront/Payment', [
            'order' => $order,
        ]);
    }

    public function stripeIntent(Order $order): JsonResponse
    {
        abort_if($order->payment_status === 'paid', 422, 'Order is already paid.');

        $settings = StoreSettings::current();

        Stripe::setApiKey(config('services.stripe.secret'));

        $intent = PaymentIntent::create([
            'amount' => $order->total_amount,
            'currency' => strtolower($settings->currency),
            'metadata' => [
                'order_number' => $order->order_number,
                'order_id' => $order->id,
            ],
        ]);

        $order->update(['payment_intent_id' => $intent->id]);

        return response()->json(['clientSecret' => $intent->client_secret]);
    }

    public function paymongoCheckout(Order $order): JsonResponse
    {
        abort_if($order->payment_status === 'paid', 422, 'Order is already paid.');

        $settings = StoreSettings::current();

        $callbackUrl = route('storefront.checkout.payment.callback', $order->order_number);

        $response = Http::withHeaders([
            'Authorization' => 'Basic '.base64_encode($settings->paymongo_secret_key.':'),
            'Content-Type' => 'application/json',
        ])->post('https://api.paymongo.com/v1/checkout_sessions', [
            'data' => [
                'attributes' => [
                    'amount' => $order->total_amount,
                    'currency' => $settings->currency,
                    'description' => "Order {$order->order_number}",
                    'line_items' => $order->items->map(fn ($item) => [
                        'currency' => $settings->currency,
                        'amount' => $item->unit_price,
                        'description' => $item->product_name,
                        'name' => $item->product_name,
                        'quantity' => $item->quantity,
                    ])->toArray(),
                    'payment_method_types' => ['card', 'gcash', 'grab_pay'],
                    'success_url' => $callbackUrl.'?status=success',
                    'cancel_url' => $callbackUrl.'?status=cancel',
                    'metadata' => ['order_number' => $order->order_number],
                ],
            ],
        ]);

        abort_if(! $response->successful(), 422, 'Could not create PayMongo checkout session.');

        $checkoutUrl = $response->json('data.attributes.checkout_url');
        $paymentIntentId = $response->json('data.id');

        $order->update(['payment_intent_id' => $paymentIntentId]);

        return response()->json(['checkoutUrl' => $checkoutUrl]);
    }

    public function paymongoCallback(Order $order, Request $request): RedirectResponse
    {
        if ($request->input('status') === 'success') {
            $order->update([
                'payment_status' => 'paid',
                'status' => OrderStatus::Processing,
                'paid_at' => now(),
            ]);

            return redirect()->route('storefront.order.confirmation', $order);
        }

        return redirect()->route('storefront.checkout.payment.show', $order->order_number)
            ->with('error', 'Payment was cancelled. Please try again.');
    }
}
