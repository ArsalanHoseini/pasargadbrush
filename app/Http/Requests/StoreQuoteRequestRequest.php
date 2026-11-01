<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Services\Customization\DimensionForm;
use App\Support\IranianMobile;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequestRequest extends FormRequest
{
    private ?Product $product = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * The product being quoted, with its dimensions form (null for a general
     * request or an unknown id — the exists rule reports the latter).
     */
    public function product(): ?Product
    {
        if ($this->product === null && $this->filled('product_id')) {
            $this->product = Product::with('dimensionFields')->find($this->input('product_id'));
        }

        return $this->product;
    }

    protected function prepareForValidation(): void
    {
        // The product page's order card sends its quantity field as "quantity".
        if (! $this->filled('requested_quantity') && $this->filled('quantity')) {
            $this->merge(['requested_quantity' => (string) $this->input('quantity')]);
        }

        // Landlines stay as typed (B2B leads often give one); a mobile number
        // is stored in the canonical form so it matches the customer's account.
        if (is_string($this->input('phone'))) {
            $mobile = IranianMobile::normalize($this->input('phone'));

            if (preg_match(IranianMobile::PATTERN, $mobile)) {
                $this->merge(['phone' => $mobile]);
            }
        }

        app(DimensionForm::class)->normalize($this, '', $this->product());
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
            ...($this->product() ? app(DimensionForm::class)->rules($this->product()) : []),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->product() ? app(DimensionForm::class)->attributes($this->product()) : [];
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
