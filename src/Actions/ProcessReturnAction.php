<?php

namespace Minishop\Actions;

use Illuminate\Support\Facades\DB;
use Minishop\Enums\OrderStatus;
use Minishop\Enums\ReturnStatus;
use Minishop\Models\Order;
use Minishop\Models\OrderReturn;
use Minishop\Models\Product;
use Minishop\Models\ProductVariant;
use Minishop\Services\StripeRefundService;

class ProcessReturnAction
{
    public function __construct(private readonly StripeRefundService $stripeRefundService) {}

    /**
     * Restock inventory for all items in the return and mark the return as received.
     */
    public function restock(OrderReturn $orderReturn): void
    {
        DB::transaction(function () use ($orderReturn): void {
            $orderReturn->load('items.orderItem');

            $variantQtys = [];
            $productQtys = [];

            foreach ($orderReturn->items as $returnItem) {
                $orderItem = $returnItem->orderItem;

                if ($orderItem->variant_id) {
                    $variantQtys[$orderItem->variant_id] = ($variantQtys[$orderItem->variant_id] ?? 0) + $returnItem->quantity;
                } else {
                    $productQtys[$orderItem->product_id] = ($productQtys[$orderItem->product_id] ?? 0) + $returnItem->quantity;
                }
            }

            // Batch lock all rows before incrementing to minimise lock window.
            // Instance-level increment fires Eloquent observers (query-builder does not).
            $variants = ProductVariant::whereIn('id', array_keys($variantQtys))->lockForUpdate()->get()->keyBy('id');
            $productModels = Product::whereIn('id', array_keys($productQtys))->lockForUpdate()->get()->keyBy('id');

            foreach ($variantQtys as $variantId => $qty) {
                $variants[$variantId]->increment('stock_quantity', $qty);
            }

            foreach ($productQtys as $productId => $qty) {
                $productModels[$productId]->increment('stock_quantity', $qty);
            }

            $orderReturn->update([
                'status' => ReturnStatus::Received,
                'restocked' => true,
            ]);
        });
    }

    /**
     * Issue the Stripe refund, update return status, and mark order as Refunded if fully refunded.
     *
     * The Stripe call is intentionally made before the DB transaction so that a
     * transactional failure cannot leave money refunded with no audit record.
     */
    public function issueRefund(OrderReturn $orderReturn): void
    {
        $orderReturn->loadMissing('order', 'items');

        $order = $orderReturn->order;
        $refundAmount = $orderReturn->items->sum('subtotal');

        $stripeRefundId = $this->stripeRefundService->refund($order, $refundAmount);

        DB::transaction(function () use ($orderReturn, $order, $refundAmount, $stripeRefundId): void {
            $orderReturn->update([
                'status' => ReturnStatus::Refunded,
                'refund_amount' => $refundAmount,
                'stripe_refund_id' => $stripeRefundId,
                'refunded_at' => now(),
            ]);

            // Maintain the order-level refunded total so the returns flow and
            // the direct admin refund share a single cap.
            $order->increment('refunded_amount', $refundAmount);
            $order->refresh();

            $order->update([
                'refunded_at' => now(),
                'status' => $order->refunded_amount >= $order->total_amount ? OrderStatus::Refunded : $order->status,
            ]);
        });
    }
}
