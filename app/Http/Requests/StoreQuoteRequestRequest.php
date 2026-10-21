<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequestRequest extends FormRequest
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
            'product_id' => ['nullable', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'requested_quantity' => ['nullable', 'string', 'max:255'],
            // Honeypot: a real visitor never fills this (hidden via CSS); a bot
            // filling every field usually does. Must stay empty.
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'لطفاً نام خود را وارد کنید.',
            'phone.required' => 'لطفاً شماره تماس خود را وارد کنید.',
            'message.required' => 'لطفاً توضیح درخواست خود را بنویسید.',
        ];
    }
}
