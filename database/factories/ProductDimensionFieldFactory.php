<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductDimensionField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductDimensionField>
 */
class ProductDimensionFieldFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => fn () => Product::create([
                'sku' => 'SKU-'.fake()->unique()->numerify('#####'),
                'slug' => 'product-'.fake()->unique()->numerify('#####'),
                'name' => 'محصول',
                'type' => 'priced',
                'is_active' => true,
            ])->id,
            'code' => fake()->randomElement(['A', 'B', 'C', 'D', 'E']),
            'label' => 'قطر خارجی',
            'unit' => 'mm',
            'is_required' => true,
            'min_value' => null,
            'max_value' => null,
            'sort_order' => 0,
        ];
    }

    public function optional(): static
    {
        return $this->state(fn () => ['is_required' => false]);
    }
}
