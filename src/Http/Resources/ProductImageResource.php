<?php

namespace Minishop\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'path' => $this->path,
            'url' => $this->url,
            'alt_text' => $this->alt_text,
            'sort_order' => $this->sort_order,
        ];
    }
}
