<?php

namespace Database\Factories;

use App\Enums\DeadlineCategory;
use App\Enums\RecurrenceType;
use App\Models\BusinessDeadline;
use App\Models\BusinessDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BusinessDeadline>
 */
class BusinessDeadlineFactory extends Factory
{
    protected $model = BusinessDeadline::class;

    public function definition(): array
    {
        $category = fake()->randomElement(DeadlineCategory::cases());

        return [
            'title' => $this->getTitleForCategory($category),
            'description' => fake()->optional()->sentence(),
            'category' => $category,
            'due_date' => fake()->dateTimeBetween('now', '+6 months'),
            'recurrence' => fake()->randomElement(RecurrenceType::cases()),
            'recurrence_rule' => null,
            'reminder_days' => [30, 14, 7, 1],
            'last_reminder_sent_at' => null,
            'completed_at' => null,
            'related_document_id' => null,
            'external_url' => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'completed_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function dueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => fake()->dateTimeBetween('+1 day', '+14 days'),
            'completed_at' => null,
        ]);
    }

    public function tax(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => DeadlineCategory::Tax,
            'title' => fake()->randomElement([
                'Federal Quarterly Tax (941)',
                'Colorado Sales Tax Filing',
                'Annual Tax Return',
            ]),
            'recurrence' => fake()->randomElement([RecurrenceType::Monthly, RecurrenceType::Quarterly]),
        ]);
    }

    public function licenseRenewal(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => DeadlineCategory::LicenseRenewal,
            'title' => 'License Renewal',
            'recurrence' => RecurrenceType::Annually,
        ]);
    }

    public function registration(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => DeadlineCategory::Registration,
            'title' => fake()->randomElement([
                'SAM.gov Registration Renewal',
                'Annual Report Filing',
            ]),
            'recurrence' => RecurrenceType::Annually,
        ]);
    }

    public function forDocument(BusinessDocument $document): static
    {
        return $this->state(fn (array $attributes) => [
            'related_document_id' => $document->id,
            'title' => $document->name . ' Renewal',
            'due_date' => $document->expiration_date ?? fake()->dateTimeBetween('+1 month', '+1 year'),
        ]);
    }

    public function withUrl(string $url = null): static
    {
        return $this->state(fn (array $attributes) => [
            'external_url' => $url ?? fake()->url(),
        ]);
    }

    private function getTitleForCategory(DeadlineCategory $category): string
    {
        return match ($category) {
            DeadlineCategory::Tax => fake()->randomElement([
                'Federal Quarterly Tax (941)',
                'Colorado Sales Tax Filing',
                'Annual Tax Return',
                'Estimated Tax Payment',
            ]),
            DeadlineCategory::LicenseRenewal => fake()->randomElement([
                'Business License Renewal',
                'Sales Tax License Renewal',
                'Contractor License Renewal',
            ]),
            DeadlineCategory::Registration => fake()->randomElement([
                'SAM.gov Registration Renewal',
                'Annual Report Filing',
                'Secretary of State Filing',
            ]),
            DeadlineCategory::Compliance => fake()->randomElement([
                'Insurance Certificate Update',
                'W-9 Collection',
                'Vendor Compliance Audit',
            ]),
            DeadlineCategory::Other => 'Business Deadline',
        };
    }
}
