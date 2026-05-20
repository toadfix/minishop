<?php

namespace Minishop\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class TaxPreviewRequest extends FormRequest
{
    /** Public endpoint — no authentication required. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country' => ['required', 'string', 'size:2', 'alpha', 'uppercase'],
            'province_code' => ['nullable', 'string', 'size:2', 'alpha', 'uppercase'],
            'subtotal' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'country.size' => 'Country must be a 2-character ISO code (e.g. CA, US).',
            'country.alpha' => 'Country must contain only letters.',
            'province_code.size' => 'Province code must be exactly 2 characters (e.g. ON, QC).',
            'subtotal.integer' => 'Subtotal must be an integer (amount in cents).',
            'subtotal.min' => 'Subtotal cannot be negative.',
        ];
    }
}
