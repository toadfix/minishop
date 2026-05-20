<?php

namespace Minishop\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.min' => 'Quantity must be 0 or greater.',
            'quantity.max' => 'Quantity cannot exceed 999.',
        ];
    }
}
