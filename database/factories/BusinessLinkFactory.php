<?php

namespace Database\Factories;

use App\Enums\LinkCategory;
use App\Models\BusinessLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BusinessLink>
 */
class BusinessLinkFactory extends Factory
{
    protected $model = BusinessLink::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Portal',
            'url' => fake()->url(),
            'category' => fake()->randomElement(LinkCategory::cases()),
            'description' => fake()->optional()->sentence(),
            'icon' => null,
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function federal(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => LinkCategory::Federal,
        ]);
    }

    public function state(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => LinkCategory::State,
        ]);
    }

    public function withIcon(string $icon): static
    {
        return $this->state(fn (array $attributes) => [
            'icon' => $icon,
        ]);
    }
}
