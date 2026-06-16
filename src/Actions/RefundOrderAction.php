<?php

namespace Minishop\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Minishop\Enums\OrderStatus;
use Minishop\Models\ActivityLog;
use Minishop\Models\Order;
use Minishop\Services\StripeRefundService;
use RuntimeException;

/**
 * Refund an order directly (partial or full) from the admin, independent of the
 * returns flow. Stripe-paid orders are refunded through the gateway; other
 * orders (e.g. COD) are recorded as manual refunds. A cumulative
 * `refunded_amount` on the order — also maintained by the returns flow — caps
 * the total so an order can never be over-refunded.
 */
class RefundOrderAction
{
    public function __construct(private readonly StripeRefundService $stripe) {}

    public function execute(Order $order, int $amountInCents, ?string $reason = null): void
    {
        if ($amountInCents <= 0) {
            throw new RuntimeException('Refund amount must be greater than zero.');
        }

        if ($amountInCents > $order->refundableAmount()) {
            throw new RuntimeException('Refund amount exceeds the refundable balance for this order.');
        }

        // Charge the gateway before recording, so a gateway failure leaves no
        // local record of money that was never moved. COD/manual orders skip it.
        $stripeRefundId = null;
        if ($order->payment_gateway === 'stripe' && ! empty($order->payment_intent_id)) {
            $stripeRefundId = $this->stripe->refund($order, $amountInCents);
        }

        DB::transaction(function () use ($order, $amountInCents, $reason, $stripeRefundId): void {
            $order->increment('refunded_amount', $amountInCents);
            $order->refresh();

            $fullyRefunded = $order->refunded_amount >= $order->total_amount;

            $order->update([
                'refunded_at' => now(),
                'payment_status' => $fullyRefunded ? 'refunded' : 'partially_refunded',
                'status' => $fullyRefunded ? OrderStatus::Refunded : $order->status,
            ]);

            ActivityLog::query()->create([
                'user_id' => Auth::id(),
                'action' => 'refunded',
                'subject_type' => 'Order',
                'subject_id' => $order->id,
                'description' => 'Refunded '.number_format($amountInCents / 100, 2)." on order \"{$order->order_number}\"",
                'properties' => [
                    'amount' => $amountInCents,
                    'reason' => $reason,
                    'stripe_refund_id' => $stripeRefundId,
                    'manual' => $stripeRefundId === null,
                ],
            ]);
        });
    }
}
