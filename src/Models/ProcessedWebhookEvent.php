<?php

namespace Minishop\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Records each payment-gateway webhook event that has been handled, keyed by
 * (gateway, event_id) with a unique index. The first handler to insert a row
 * claims the event; later deliveries of the same event are no-ops.
 */
class ProcessedWebhookEvent extends Model
{
    protected $fillable = [
        'gateway',
        'event_id',
        'type',
    ];
}
