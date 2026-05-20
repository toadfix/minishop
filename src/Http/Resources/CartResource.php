<?php

namespace Minishop\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_count' => $this->items->sum('quantity'),
            'subtotal' => $this->items->sum(fn ($item) => $item->unit_price * $item->quantity),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
