<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAttribute>
 */
class ProductAttributeFactory extends Factory
{
    protected $model = ProductAttribute::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['string', 'integer', 'float', 'boolean', 'select']);

        $value = match ($type) {
            'integer' => (string) $this->faker->numberBetween(1, 100),
            'float' => (string) $this->faker->randomFloat(2, 0, 500),
            'boolean' => $this->faker->boolean() ? 'true' : 'false',
            'select' => $this->faker->randomElement(['Option A', 'Option B', 'Option C']),
            default => $this->faker->colorName(),
        };

        return [
            'product_id' => Product::factory(),
            'name' => ucfirst($this->faker->unique()->word()),
            'type' => $type,
            'value' => $value,
        ];
    }
}
