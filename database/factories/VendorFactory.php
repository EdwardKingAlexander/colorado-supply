<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = Vendor::class;
    
    public function definition(): array
    {
        return [
            'user_id' => 1,
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail,
            'phone' => fake()->phoneNumber(),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraph(),
            'logo' => fake()->imageUrl(640, 480, 'business', true)
        ];
    }
}
