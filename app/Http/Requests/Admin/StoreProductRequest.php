<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductDimensionField;
use App\Models\ProductHighlight;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
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
        $productId = $this->route('product')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'primary_category_id' => ['nullable', 'exists:product_categories,id'],
            'category_ids' => ['array'],
            'category_ids.*' => ['exists:product_categories,id'],
            'type' => ['required', 'in:quote,priced'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'stock_status' => ['nullable', 'in:in_stock,out_of_stock,made_to_order'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'image_ids' => ['array'],
            'image_ids.*' => ['exists:media,id'],

            'specifications' => ['array'],
            'specifications.*.key' => ['nullable', 'string', 'max:255'],
            'specifications.*.value' => ['nullable', 'string', 'max:255'],

            'variants' => ['array'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.label' => ['required_with:variants', 'string', 'max:255'],
            // A priced variant at 0 would be orderable for free; quote products
            // never show variant prices, so 0 stays allowed there.
            'variants.*.price_toman' => ['required_with:variants', 'integer', $this->input('type') === 'priced' ? 'min:1' : 'min:0'],
            'variants.*.stock_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.is_default' => ['boolean'],

            // SVG excluded for the same XSS reason as the media library upload.
            'schematics' => ['nullable', 'array', 'max:6'],
            'schematics.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_schematic_ids' => ['nullable', 'array'],
            'remove_schematic_ids.*' => ['integer'],

            'highlights' => ['nullable', 'array', 'max:'.ProductHighlight::MAX_PER_PRODUCT],
            'highlights.*.id' => ['nullable', 'integer'],
            'highlights.*.title' => ['required', 'string', 'max:120'],
            'highlights.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'highlights.*.remove_image' => ['nullable', 'boolean'],

            'application_ids' => ['nullable', 'array'],
            'application_ids.*' => ['integer', 'exists:applications,id'],

            'dimension_fields' => ['array'],
            'dimension_fields.*.id' => ['nullable', 'integer'],
            // Missing type = a numeric dimension (the only kind before choices existed).
            'dimension_fields.*.type' => ['nullable', Rule::in([ProductDimensionField::TYPE_NUMBER, ProductDimensionField::TYPE_CHOICE])],
            // A dimension needs its letter on the drawing; a choice (e.g. fiber material) usually has none.
            'dimension_fields.*.code' => ['required_unless:dimension_fields.*.type,'.ProductDimensionField::TYPE_CHOICE, 'nullable', 'string', 'max:20'],
            'dimension_fields.*.options' => ['required_if:dimension_fields.*.type,'.ProductDimensionField::TYPE_CHOICE, 'nullable', 'string', 'max:1000'],
            'dimension_fields.*.label' => ['required', 'string', 'max:255'],
            'dimension_fields.*.unit' => ['nullable', Rule::in(array_keys(ProductDimensionField::UNITS))],
            'dimension_fields.*.is_required' => ['boolean'],
            'dimension_fields.*.min_value' => ['nullable', 'numeric', 'min:0'],
            'dimension_fields.*.max_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $codes = collect($this->input('dimension_fields', []))->pluck('code')->filter(fn ($code) => filled($code))->map(fn ($code) => trim((string) $code));

                foreach ($codes->duplicates() as $index => $code) {
                    $validator->errors()->add("dimension_fields.{$index}.code", "حرف «{$code}» برای دو بُعد تکرار شده است.");
                }

                foreach ($this->input('dimension_fields', []) as $index => $field) {
                    $min = $field['min_value'] ?? null;
                    $max = $field['max_value'] ?? null;

                    if (is_numeric($min) && is_numeric($max) && (float) $min > (float) $max) {
                        $validator->errors()->add(
                            "dimension_fields.{$index}.max_value",
                            'در فیلد ابعاد «'.($field['code'] ?? '').'» حداکثر نباید از حداقل کمتر باشد.',
                        );
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
        return [
            'schematics' => 'تصاویر شماتیک',
            'highlights' => 'ویژگی‌های کلیدی',
            'highlights.*.title' => 'عنوان ویژگی',
            'highlights.*.image' => 'تصویر ویژگی',
            'application_ids.*' => 'کاربرد',
            'schematics.*' => 'تصویر شماتیک',
            'dimension_fields.*.type' => 'نوع فیلد',
            'dimension_fields.*.options' => 'گزینه‌ها',
            'dimension_fields.*.code' => 'حرف روی شکل',
            'dimension_fields.*.label' => 'عنوان بُعد',
            'dimension_fields.*.unit' => 'واحد',
            'dimension_fields.*.min_value' => 'حداقل',
            'dimension_fields.*.max_value' => 'حداکثر',
        ];
    }
}
