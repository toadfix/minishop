<?php

namespace Minishop\Observers;

use Illuminate\Support\Facades\Auth;
use Minishop\Models\ActivityLog;
use Minishop\Models\OrderReturn;

class OrderReturnObserver
{
    public function created(OrderReturn $orderReturn): void
    {
        ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => 'created',
            'subject_type' => 'OrderReturn',
            'subject_id' => $orderReturn->id,
            'description' => "Created return {$orderReturn->return_number} for order #{$orderReturn->order_id}",
            'properties' => null,
        ]);
    }

    public function updated(OrderReturn $orderReturn): void
    {
        $changed = $orderReturn->getChanges();
        unset($changed['updated_at']);

        if (empty($changed)) {
            return;
        }

        $description = isset($changed['status'])
            ? "Updated return {$orderReturn->return_number} status to {$orderReturn->status->label()}"
            : "Updated return {$orderReturn->return_number}";

        ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => 'updated',
            'subject_type' => 'OrderReturn',
            'subject_id' => $orderReturn->id,
            'description' => $description,
            'properties' => $changed,
        ]);
    }
}
