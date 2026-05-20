<?php

namespace Minishop\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_item_id' => $this->order_item_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'subtotal' => $this->subtotal,
            'order_item' => $this->whenLoaded('orderItem', fn () => [
                'id' => $this->orderItem->id,
                'product_name' => $this->orderItem->product_name,
                'product_sku' => $this->orderItem->product_sku,
                'quantity' => $this->orderItem->quantity,
                'unit_price' => $this->orderItem->unit_price,
            ]),
        ];
    }
}
