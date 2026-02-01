<?php

namespace Database\Factories;

use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MilSpecPart>
 */
class MilSpecPartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nsn' => $this->faker->unique()->numerify('####-##-###-####'),
            'description' => $this->faker->sentence(),
            'manufacturer_part_number' => $this->faker->word() . '-' . $this->faker->numerify('####'),
            'manufacturer_id' => Manufacturer::factory(), // This will create a manufacturer if none exists
        ];
    }
}
