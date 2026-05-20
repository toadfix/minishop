<?php

namespace Minishop\Observers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Minishop\Enums\OrderStatus;
use Minishop\Mail\OrderStatusChangedMail;
use Minishop\Models\ActivityLog;
use Minishop\Models\Order;

class OrderObserver
{
    public function created(Order $order): void
    {
        ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => 'created',
            'subject_type' => 'Order',
            'subject_id' => $order->id,
            'description' => "Created order {$order->order_number}",
            'properties' => null,
        ]);
    }

    public function updated(Order $order): void
    {
        $changed = $order->getChanges();
        unset($changed['updated_at']);

        if (empty($changed)) {
            return;
        }

        $description = isset($changed['status'])
            ? "Updated order {$order->order_number} status to {$order->status->label()}"
            : "Updated order {$order->order_number}";

        ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => 'updated',
            'subject_type' => 'Order',
            'subject_id' => $order->id,
            'description' => $description,
            'properties' => $changed,
        ]);

        $emailStatuses = [OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Cancelled, OrderStatus::Refunded];

        if (isset($changed['status']) && in_array($order->status, $emailStatuses)) {
            $customerEmail = $order->customer?->user?->email;

            if ($customerEmail) {
                Mail::to($customerEmail)->queue(
                    new OrderStatusChangedMail($order->load(['items', 'customer.user', 'shippingMethod']))
                );
            }
        }
    }

    public function deleted(Order $order): void
    {
        ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'subject_type' => 'Order',
            'subject_id' => $order->id,
            'description' => "Deleted order {$order->order_number}",
            'properties' => null,
        ]);
    }
}
