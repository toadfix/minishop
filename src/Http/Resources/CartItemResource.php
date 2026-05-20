<?php

namespace Minishop\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'subtotal' => $this->unit_price * $this->quantity,
            'product' => new ProductResource($this->whenLoaded('product')),
            'variant' => $this->whenLoaded('variant'),
        ];
    }
}
