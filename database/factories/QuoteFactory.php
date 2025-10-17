<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quote>
 */
class QuoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quote_number' => 'Q-' . strtoupper(uniqid()),
            'status' => fake()->randomElement(['draft', 'sent', 'ordered', 'cancelled', 'expired']),
            'customer_id' => \App\Models\Customer::factory(),
            'currency' => 'USD',
            'tax_rate' => fake()->randomFloat(2, 0, 15),
            'discount_amount' => fake()->randomFloat(2, 0, 100),
            'subtotal' => fake()->randomFloat(2, 100, 5000),
            'tax_total' => fake()->randomFloat(2, 10, 500),
            'grand_total' => fake()->randomFloat(2, 110, 5500),
            'sales_rep_id' => \App\Models\User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
