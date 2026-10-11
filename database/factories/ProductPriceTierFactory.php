<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductPriceTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPriceTier>
 */
class ProductPriceTierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'min_quantity' => fake()->unique()->numberBetween(2, 1000),
            'unit_price' => fake()->randomFloat(2, 1, 500),
        ];
    }
}
