<?php

namespace Minishop\Payments\Gateways;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Minishop\Enums\OrderStatus;
use Minishop\Mail\OrderConfirmationMail;
use Minishop\Models\Order;
use Minishop\Models\ProcessedWebhookEvent;
use Minishop\Models\StoreSettings;
use Minishop\Payments\Contracts\PaymentGatewayContract;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeGateway implements PaymentGatewayContract
{
    public function name(): string
    {
        return 'stripe';
    }

    public function requiresPaymentStep(): bool
    {
        return true;
    }

    public function initiate(Order $order, Request $request): JsonResponse|RedirectResponse
    {
        $settings = StoreSettings::current();

        Stripe::setApiKey(config('services.stripe.secret'));

        $reusableStatuses = ['requires_payment_method', 'requires_confirmation', 'requires_action'];

        if ($order->payment_intent_id) {
            $existing = PaymentIntent::retrieve($order->payment_intent_id);

            if (in_array($existing->status, $reusableStatuses, true)) {
                $intent = $existing;
            } else {
                // Intent is canceled or succeeded — create a fresh one.
                $intent = PaymentIntent::create([
                    'amount' => $order->total_amount,
                    'currency' => strtolower($settings->currency),
                    'metadata' => [
                        'order_number' => $order->order_number,
                        'order_id' => $order->id,
                    ],
                ]);

                $order->update(['payment_intent_id' => $intent->id]);
            }
        } else {
            $intent = PaymentIntent::create([
                'amount' => $order->total_amount,
                'currency' => strtolower($settings->currency),
                'metadata' => [
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                ],
            ]);

            $order->update(['payment_intent_id' => $intent->id]);
        }

        return response()->json(['clientSecret' => $intent->client_secret]);
    }

    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, config('services.stripe.webhook_secret'));
        } catch (UnexpectedValueException) {
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException) {
            return response('Invalid signature', 400);
        }

        // Record the event before acting on it. The unique (gateway, event_id)
        // index makes a redelivery of the same event a no-op, so Stripe retries
        // can never double-fulfil or double-email an order. Real Stripe events
        // always carry an id; fall back to a content hash if one is ever absent.
        $eventId = $event->id ?: sha1($payload);

        DB::transaction(function () use ($event, $eventId): void {
            $claim = ProcessedWebhookEvent::query()->firstOrCreate(
                ['gateway' => 'stripe', 'event_id' => $eventId],
                ['type' => $event->type],
            );

            if (! $claim->wasRecentlyCreated) {
                return;
            }

            if ($event->type !== 'payment_intent.succeeded') {
                return;
            }

            $order = Order::query()
                ->where('payment_intent_id', $event->data->object->id)
                ->where('payment_status', '!=', 'paid')
                ->lockForUpdate()
                ->with(['customer.user', 'items', 'shippingMethod', 'coupon'])
                ->first();

            if (! $order || ! $order->customer?->user) {
                return;
            }

            $order->update([
                'payment_status' => 'paid',
                'status' => OrderStatus::Processing,
                'paid_at' => now(),
            ]);

            Mail::to($order->customer->user->email)
                ->queue(new OrderConfirmationMail($order));
        });

        return response('OK', 200);
    }
}
