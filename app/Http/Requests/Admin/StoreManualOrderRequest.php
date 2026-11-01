<?php

namespace App\Http\Requests\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Services\Cart\CartManager;
use App\Services\Checkout\ShippingMethod;
use App\Services\Customization\DimensionForm;
use App\Support\IranianMobile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * An order entered by staff. Each line may be any product (quote-only ones
 * included) at a price staff set; a product with a dimensions form needs
 * its dimensions on the line, validated by the same rules customers face.
 */
class StoreManualOrderRequest extends FormRequest
{
    /** Statuses a newly entered order may start in. */
    public const INITIAL_STATUSES = ['pending', 'confirmed', 'processing'];

    /** @var Collection<int, Product>|null */
    private ?Collection $products = null;

    public function authorize(): bool
    {
        return true; // gated by the admin.staff route middleware
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => IranianMobile::normalize($this->input('phone'))]);
        }

        $dimensionForm = app(DimensionForm::class);

        foreach ($this->lines() as $index => $line) {
            $dimensionForm->normalize($this, "items.{$index}.", $this->products()->get($line['product_id'] ?? null));
        }
    }

    /**
     * The products referenced by the submitted lines, with their dimension
     * fields, keyed by id.
     *
     * @return Collection<int, Product>
     */
    public function products(): Collection
    {
        return $this->products ??= Product::query()
            ->with('dimensionFields', 'variants')
            ->whereIn('id', collect($this->lines())->pluck('product_id')->filter()->all())
            ->get()
            ->keyBy('id');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'quote_request_id' => ['nullable', Rule::exists('quote_requests', 'id')->whereNull('order_id')],

            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:'.IranianMobile::PATTERN],
            'email' => ['nullable', 'email', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address_line' => ['required', 'string', 'max:500'],
            'postal_code' => ['nullable', 'string', 'max:20'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.CartManager::MAX_QUANTITY],
            'items.*.unit_price_toman' => ['required', 'integer', 'min:0'],

            'shipping_method' => ['required', Rule::enum(ShippingMethod::class)],
            'shipping_cost_toman' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(self::INITIAL_STATUSES)],
            'payment_status' => ['required', Rule::in(array_keys(Order::PAYMENT_STATUS_LABELS))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'notify_customer' => ['boolean'],
        ];

        $dimensionForm = app(DimensionForm::class);

        foreach ($this->lines() as $index => $line) {
            $product = $this->products()->get($line['product_id'] ?? null);

            if ($product) {
                $rules += $dimensionForm->rules($product, "items.{$index}.");
            }
        }

        return $rules;
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->lines() as $index => $line) {
                    $product = $this->products()->get($line['product_id'] ?? null);
                    $variantId = $line['product_variant_id'] ?? null;

                    if ($product && filled($variantId) && ! $product->variants->contains('id', (int) $variantId)) {
                        $validator->errors()->add("items.{$index}.product_variant_id", 'ردیف '.($index + 1).': تنوع انتخاب‌شده متعلق به این محصول نیست.');
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'items' => 'اقلام سفارش',
            'items.*.product_id' => 'محصول',
            'items.*.quantity' => 'تعداد',
            'items.*.unit_price_toman' => 'قیمت واحد',
            'items.*.name' => 'عنوان ردیف',
            'shipping_method' => 'روش ارسال',
            'shipping_cost_toman' => 'هزینه ارسال',
            'status' => 'وضعیت سفارش',
            'payment_status' => 'وضعیت پرداخت',
            'quote_request_id' => 'درخواست استعلام',
        ];

        $dimensionForm = app(DimensionForm::class);

        foreach ($this->lines() as $index => $line) {
            $product = $this->products()->get($line['product_id'] ?? null);

            if ($product) {
                $attributes += $dimensionForm->attributes($product, "items.{$index}.", 'ردیف '.($index + 1).' — ');
            }
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'شماره موبایل معتبر نیست — مثال: ۰۹۱۲۱۲۳۴۵۶۷',
            'quote_request_id.exists' => 'برای این درخواست استعلام قبلاً سفارش ثبت شده است.',
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    private function lines(): array
    {
        $items = $this->input('items');

        return is_array($items) ? array_filter($items, 'is_array') : [];
    }
}
