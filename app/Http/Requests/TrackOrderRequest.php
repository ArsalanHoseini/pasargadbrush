<?php

namespace App\Http\Requests;

use App\Support\IranianMobile;
use App\Support\PersianDigits;
use Illuminate\Foundation\Http\FormRequest;

class TrackOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Customers copy the order number from an SMS/email or retype it on a
     * Persian keyboard, so accept Persian digits, lowercase and stray spaces.
     */
    protected function prepareForValidation(): void
    {
        $normalized = [];

        if (is_string($this->input('order_number'))) {
            $normalized['order_number'] = strtoupper(preg_replace('/\s+/u', '', PersianDigits::toLatin($this->input('order_number'))) ?? '');
        }

        if (is_string($this->input('phone'))) {
            $normalized['phone'] = IranianMobile::normalize($this->input('phone'));
        }

        $this->merge($normalized);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_number' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
        ];
    }
}
