<?php

namespace Database\Factories;

use App\Models\SamOpportunity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SamOpportunity>
 */
class SamOpportunityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'notice_id' => fake()->unique()->uuid(),
            'title' => fake()->sentence(5),
            'agency' => fake()->company(),
            'response_deadline' => fake()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
            'description' => fake()->paragraph(),
            'notice_type' => 'Solicitation',
            'naics_code' => '423840',
            'psc_code' => '5340',
            'posted_date' => now()->subDay(),
            'raw_data' => [],
        ];
    }
}
