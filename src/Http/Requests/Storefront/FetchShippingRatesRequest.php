<?php

namespace Minishop\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class FetchShippingRatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'postcode' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.product_id.exists' => 'One or more products are invalid.',
            'items.*.variant_id.exists' => 'One or more variants are invalid.',
            'postcode.required' => 'A destination postal code is required.',
            'country.required' => 'A destination country is required.',
        ];
    }
}
