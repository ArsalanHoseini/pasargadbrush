<?php

namespace Database\Factories;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location' => 'header',
            'label' => fake()->words(2, true),
            'type' => MenuItem::TYPE_LINK,
            'url' => '/'.fake()->slug(2),
            'open_in_new_tab' => false,
            'sort_order' => 0,
        ];
    }

    public function footer(): static
    {
        return $this->state(fn () => ['location' => 'footer']);
    }

    public function categories(): static
    {
        return $this->state(fn () => ['type' => MenuItem::TYPE_CATEGORIES, 'label' => 'محصولات', 'url' => null]);
    }
}
