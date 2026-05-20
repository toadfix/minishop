<?php

namespace Minishop\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderReturnResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'return_number' => $this->return_number,
            'order_id' => $this->order_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'reason' => $this->reason->value,
            'reason_label' => $this->reason->label(),
            'notes' => $this->notes,
            'admin_notes' => $this->admin_notes,
            'refund_amount' => $this->refund_amount,
            'stripe_refund_id' => $this->stripe_refund_id,
            'restocked' => $this->restocked,
            'refunded_at' => $this->refunded_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'allowed_transitions' => $this->status->allowedTransitions(),
            'order' => $this->order ? [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'total_amount' => $this->order->total_amount,
                'status' => $this->order->status->value,
                'status_label' => $this->order->status->label(),
            ] : null,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(
                fn ($item) => (new ReturnItemResource($item))->toArray($request),
            )->values()),
        ];
    }
}
