<?php

namespace App\Services\Customization;

use App\Models\Product;
use App\Models\ProductDimensionField;
use App\Support\PersianDigits;
use Illuminate\Http\Request;

/**
 * The customer-facing dimensions form of a product (defined per product by
 * staff), shared by the cart, the quote-request flow and staff-entered
 * orders: request normalization, validation rules, Persian field names for
 * error messages, and the snapshot stored on cart lines, order lines and
 * quote requests.
 *
 * Inputs: {prefix}dimensions[{field id}] and {prefix}customization_notes —
 * the prefix is empty for the single-product forms, and e.g. "items.2." for
 * one line of a staff-entered order.
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
    public function normalize(Request $request, string $prefix = ''): void
    {
        $key = $prefix.'dimensions';
        $dimensions = $request->input($key);

        if (! is_array($dimensions)) {
            return;
        }

        $normalized = array_map(
            fn ($value) => is_string($value)
                ? trim(str_replace(['٫', ','], '.', PersianDigits::toLatin($value)))
                : $value,
            $dimensions,
        );

        $input = $request->all();
        data_set($input, $key, $normalized);
        $request->merge($input);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(Product $product, string $prefix = ''): array
    {
        $rules = [
            $prefix.'dimensions' => [$product->requiresDimensions() ? 'required' : 'nullable', 'array'],
            $prefix.self::NOTES_INPUT => ['nullable', 'string', 'max:1000'],
        ];

        foreach ($product->dimensionFields as $field) {
            $fieldRules = [$field->is_required ? 'required' : 'nullable', 'numeric', 'gt:0'];

            if ($field->min_value !== null) {
                $fieldRules[] = 'min:'.$field->min_value;
            }

            if ($field->max_value !== null) {
                $fieldRules[] = 'max:'.$field->max_value;
            }

            $rules[$prefix.'dimensions.'.$field->id] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @param  string  $labelPrefix  Prepended to each name, e.g. "ردیف ۲ — ".
     * @return array<string, string>
     */
    public function attributes(Product $product, string $prefix = '', string $labelPrefix = ''): array
    {
        return $product->dimensionFields
            ->mapWithKeys(fn (ProductDimensionField $field) => [
                $prefix.'dimensions.'.$field->id => "{$labelPrefix}{$field->label} ({$field->code})",
            ])
            ->put($prefix.self::NOTES_INPUT, $labelPrefix.'توضیحات')
            ->all();
    }

    /**
     * Freezes the field definitions together with the values, so a later
     * edit to the product's form never changes what a customer submitted.
     *
     * @param  array<string, mixed>  $validated  Holding "dimensions" and "customization_notes" (unprefixed).
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

    /**
     * The reverse of snapshot(): field id => value, matched by letter, so a
     * quote request's dimensions can pre-fill a new order line for the same
     * product.
     *
     * @param  array<string, mixed>|null  $snapshot
     * @return array<int, string>
     */
    public function valuesFromSnapshot(Product $product, ?array $snapshot): array
    {
        $byCode = collect($snapshot['dimensions'] ?? [])->pluck('value', 'code');

        return $product->dimensionFields
            ->filter(fn (ProductDimensionField $field) => $byCode->has($field->code))
            ->mapWithKeys(fn (ProductDimensionField $field) => [$field->id => $byCode->get($field->code)])
            ->all();
    }
}
