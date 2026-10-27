<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
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
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail,
            'phone' => fake()->numerify('###-###-####'), // phoneNumber() can add an extension (x123), which the form's tel() rule rejects
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraph(),
            'logo' => fake()->imageUrl(640, 480, 'business', true),
        ];
    }
}
