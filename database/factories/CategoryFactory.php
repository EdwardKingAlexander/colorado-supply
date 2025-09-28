<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = ucfirst($this->faker->unique()->words(mt_rand(1, 3), true));

        // 30% chance to attach to an existing parent (if any exist)
        $maybeParentId = null;
        if ($this->faker->boolean(30)) {
            $maybeParentId = Category::query()->inRandomOrder()->value('id'); // null if none yet
        }

        return [
            'parent_id'   => $maybeParentId,                // nullable OK
            'name'        => $name,
            // add short suffix to reduce unique-collisions when seeding fast
            'slug'        => Str::slug($name) . '-' . Str::lower(Str::random(5)),
            'description' => $this->faker->sentence(8),
            'image'       => null,
        ];
    }

    /**
     * Force a root category (no parent).
     */
    public function root(): self
    {
        return $this->state(fn () => ['parent_id' => null]);
    }

    /**
     * Force a child category (will create a parent if none exist yet).
     */
    public function child(): self
    {
        return $this->state(function () {
            $parentId = Category::query()->inRandomOrder()->value('id')
                ?? Category::factory()->root()->create()->id;

            return ['parent_id' => $parentId];
        });
    }
}
