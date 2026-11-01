<?php

namespace App\Services\Customization;

use App\Models\Product;
use App\Models\ProductDimensionField;
use App\Support\PersianDigits;
use Illuminate\Http\Request;

/**
 * The customer-facing dimensions form of a product (defined per product by
 * staff), shared by the cart and the quote-request flow: request
 * normalization, validation rules, Persian field names for error messages,
 * and the snapshot stored on cart lines, order lines and quote requests.
 *
 * Inputs: dimensions[{field id}] and customization_notes.
 *
 * Snapshot shape:
 *   ['dimensions' => [['code' => 'A', 'label' => 'قطر خارجی', 'unit' => 'mm', 'value' => '120'], ...],
 *    'notes' => ?string]
 */
class DimensionForm
{
    public const NOTES_INPUT = 'customization_notes';

    /**
     * Persian keyboards type Persian digits and «٫» as the decimal separator.
     */
    public function normalize(Request $request): void
    {
        $dimensions = $request->input('dimensions');

        if (! is_array($dimensions)) {
            return;
        }

        $request->merge(['dimensions' => array_map(
            fn ($value) => is_string($value)
                ? trim(str_replace(['٫', ','], '.', PersianDigits::toLatin($value)))
                : $value,
            $dimensions,
        )]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(Product $product): array
    {
        $rules = [
            'dimensions' => [$product->requiresDimensions() ? 'required' : 'nullable', 'array'],
            self::NOTES_INPUT => ['nullable', 'string', 'max:1000'],
        ];

        foreach ($product->dimensionFields as $field) {
            $fieldRules = [$field->is_required ? 'required' : 'nullable', 'numeric', 'gt:0'];

            if ($field->min_value !== null) {
                $fieldRules[] = 'min:'.$field->min_value;
            }

            if ($field->max_value !== null) {
                $fieldRules[] = 'max:'.$field->max_value;
            }

            $rules['dimensions.'.$field->id] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(Product $product): array
    {
        return $product->dimensionFields
            ->mapWithKeys(fn (ProductDimensionField $field) => [
                'dimensions.'.$field->id => "{$field->label} ({$field->code})",
            ])
            ->put(self::NOTES_INPUT, 'توضیحات')
            ->all();
    }

    /**
     * Freezes the field definitions together with the values, so a later
     * edit to the product's form never changes what a customer submitted.
     *
     * @param  array<string, mixed>  $validated
     * @return array{dimensions: list<array{code: string, label: string, unit: string, value: string}>, notes: ?string}|null
     */
    public function snapshot(Product $product, array $validated): ?array
    {
        if (! $product->requiresDimensions()) {
            return null;
        }

        $values = $validated['dimensions'] ?? [];

        $dimensions = $product->dimensionFields
            ->filter(fn (ProductDimensionField $field) => filled($values[$field->id] ?? null))
            ->map(fn (ProductDimensionField $field) => [
                'code' => $field->code,
                'label' => $field->label,
                'unit' => $field->unit,
                'value' => (string) ($values[$field->id] + 0),
            ])
            ->values()
            ->all();

        return [
            'dimensions' => $dimensions,
            'notes' => filled($validated[self::NOTES_INPUT] ?? null) ? $validated[self::NOTES_INPUT] : null,
        ];
    }
}
