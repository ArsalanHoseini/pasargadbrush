<?php

namespace App\Http\Requests;

use App\Support\IranianMobile;
use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A logged-in customer always orders with their account's verified
     * number (so the order lands in their dashboard), and may pick an
     * address-book entry instead of typing an address.
     */
    protected function prepareForValidation(): void
    {
        $customer = $this->user('customer');

        if ($customer) {
            $this->merge(['phone' => $customer->phone]);

            $savedAddress = filled($this->input('saved_address_id'))
                ? $customer->savedAddresses()->whereKey($this->input('saved_address_id'))->first()
                : null;

            if ($savedAddress) {
                $this->merge($savedAddress->only(['province', 'city', 'address_line', 'postal_code']));
            } else {
                $this->merge(['saved_address_id' => null]);
            }
        } elseif (is_string($this->input('phone'))) {
            $this->merge(['phone' => IranianMobile::normalize($this->input('phone'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'saved_address_id' => ['nullable', 'integer'],
            'save_address' => ['boolean'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:'.IranianMobile::PATTERN],
            'email' => ['nullable', 'email', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address_line' => ['required', 'string', 'max:500'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'شماره موبایل معتبر نیست — مثال: ۰۹۱۲۱۲۳۴۵۶۷',
        ];
    }
}
