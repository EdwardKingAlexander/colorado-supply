<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\BusinessDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BusinessDocument>
 */
class BusinessDocumentFactory extends Factory
{
    protected $model = BusinessDocument::class;

    public function definition(): array
    {
        $type = fake()->randomElement(DocumentType::cases());
        $issueDate = fake()->dateTimeBetween('-2 years', '-1 month');
        $hasExpiration = fake()->boolean(70);

        return [
            'type' => $type,
            'name' => $this->getNameForType($type),
            'description' => fake()->optional()->sentence(),
            'document_number' => fake()->optional(0.8)->numerify('###-###-####'),
            'issuing_authority' => $this->getAuthorityForType($type),
            'issue_date' => $issueDate,
            'expiration_date' => $hasExpiration ? fake()->dateTimeBetween($issueDate, '+2 years') : null,
            'file_path' => null,
            'status' => DocumentStatus::Active,
            'metadata' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiration_date' => fake()->dateTimeBetween('-1 year', '-1 day'),
            'status' => DocumentStatus::Expired,
        ]);
    }

    public function pendingRenewal(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiration_date' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'status' => DocumentStatus::PendingRenewal,
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiration_date' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'status' => DocumentStatus::Active,
        ]);
    }

    public function license(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DocumentType::License,
            'name' => 'Colorado Sales Tax License',
            'issuing_authority' => 'Colorado Department of Revenue',
        ]);
    }

    public function insurance(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DocumentType::Insurance,
            'name' => 'General Liability Insurance',
            'issuing_authority' => fake()->company() . ' Insurance',
        ]);
    }

    public function registration(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DocumentType::Registration,
            'name' => 'SAM.gov Registration',
            'issuing_authority' => 'System for Award Management',
        ]);
    }

    private function getNameForType(DocumentType $type): string
    {
        return match ($type) {
            DocumentType::License => fake()->randomElement([
                'Colorado Sales Tax License',
                'Business License',
                'Contractor License',
            ]),
            DocumentType::Insurance => fake()->randomElement([
                'General Liability Insurance',
                'Workers Compensation Insurance',
                'Professional Liability Insurance',
            ]),
            DocumentType::Registration => fake()->randomElement([
                'SAM.gov Registration',
                'Colorado Secretary of State Registration',
                'DUNS Registration',
            ]),
            DocumentType::TaxDocument => fake()->randomElement([
                'EIN Confirmation Letter',
                'W-9 Form',
                'Tax Exemption Certificate',
            ]),
            DocumentType::Contract => fake()->randomElement([
                'Vendor Agreement',
                'Service Contract',
                'Supply Agreement',
            ]),
            DocumentType::Other => 'Business Document',
        };
    }

    private function getAuthorityForType(DocumentType $type): string
    {
        return match ($type) {
            DocumentType::License => 'Colorado Department of Revenue',
            DocumentType::Insurance => fake()->company() . ' Insurance',
            DocumentType::Registration => 'System for Award Management',
            DocumentType::TaxDocument => 'Internal Revenue Service',
            DocumentType::Contract => fake()->company(),
            DocumentType::Other => fake()->company(),
        };
    }
}
