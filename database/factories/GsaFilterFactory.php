<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GsaFilter>
 */
class GsaFilterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'naics',
            'code' => fake()->numerify('######'),
            'description' => fake()->words(3, true),
            'enabled' => true,
        ];
    }
}
