<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // A priced product is added by its variant; a quote-only product by itself.
            'product_variant_id' => ['required_without:product_id', 'nullable', 'integer', 'exists:product_variants,id'],
            'product_id' => ['required_without:product_variant_id', 'nullable', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
