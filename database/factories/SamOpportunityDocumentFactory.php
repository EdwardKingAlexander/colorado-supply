<?php

namespace Database\Factories;

use App\Models\SamOpportunity;
use App\Models\SamOpportunityDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SamOpportunityDocument>
 */
class SamOpportunityDocumentFactory extends Factory
{
    public function definition(): array
    {
        $filename = fake()->uuid().'.txt';

        return [
            'sam_opportunity_id' => SamOpportunity::factory(),
            'uploaded_by_user_id' => User::factory(),
            'storage_path' => 'sam_documents/1/'.$filename,
            'disk' => 'sam_documents',
            'original_filename' => $filename,
            'mime_type' => 'text/plain',
            'size_bytes' => 100,
        ];
    }
}
