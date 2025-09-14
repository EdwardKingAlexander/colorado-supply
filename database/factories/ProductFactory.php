<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => 1,
            'category_id' => fake()->numberBetween(1, 5),
            'name' => fake()->word(),
            'slug' => fake()->unique()->slug(),
            'sku' => strtoupper(fake()->bothify('???-########')),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 5, 500),
            'stock' => fake()->numberBetween(0, 100),
            'is_active' => fake()->boolean(80),
        ];
    }
}
