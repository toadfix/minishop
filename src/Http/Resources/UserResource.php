<?php

namespace Minishop\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer
                ? ['id' => $this->customer->id, 'phone' => $this->customer->phone, 'is_active' => $this->customer->is_active]
                : null
            ),
        ];
    }
}
