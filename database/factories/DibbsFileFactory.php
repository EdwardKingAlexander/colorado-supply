<?php

namespace Database\Factories;

use App\Models\DibbsFile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DibbsFile>
 */
class DibbsFileFactory extends Factory
{
    public function definition(): array
    {
        // Name and path follow posting_date (also when a test overrides it). A random
        // name could collide with a fixture day (e.g. in261002.txt) and make the
        // download job skip that file as "already imported", a 1-in-10 flake.
        return [
            'posting_date' => fake()->dateTimeBetween('-20 days'),
            'name' => fn (array $attributes) => 'in'.Carbon::parse($attributes['posting_date'])->format('ymd').'.txt',
            'type' => DibbsFile::TYPE_INDEX,
            'source' => 'automated',
            'sha256' => hash('sha256', fake()->unique()->uuid()),
            'size_bytes' => 1400,
            'stored_path' => fn (array $attributes) => 'dibbs/'.$attributes['name'],
            'status' => DibbsFile::STATUS_IMPORTED,
        ];
    }
}
