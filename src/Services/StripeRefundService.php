<?php

namespace Minishop\Services;

use Minishop\Models\Order;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeRefundService
{
    private StripeClient $stripe;

    /**
     * Issue a refund against the order's payment intent.
     *
     * @throws ApiErrorException
     * @throws RuntimeException
     */
    public function refund(Order $order, int $amountInCents): string
    {
        $secret = config('services.stripe.secret');

        if (empty($secret)) {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        $this->stripe = new StripeClient($secret);

        if (empty($order->payment_intent_id)) {
            throw new RuntimeException("Order {$order->order_number} has no payment intent ID to refund.");
        }

        if ($order->payment_gateway !== 'stripe') {
            throw new RuntimeException("Order {$order->order_number} was not paid via Stripe.");
        }

        $refund = $this->stripe->refunds->create([
            'payment_intent' => $order->payment_intent_id,
            'amount' => $amountInCents,
        ]);

        return $refund->id;
    }
}
