<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\ContractDocument;
use App\Models\SamOpportunity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContractDocument>
 */
class ContractDocumentFactory extends Factory
{
    protected $model = ContractDocument::class;

    public function definition(): array
    {
        $filename = fake()->words(3, true) . '.pdf';

        return [
            'original_filename' => $filename,
            'mime_type' => 'application/pdf',
            'storage_disk' => ContractDocument::DISK,
            'storage_path' => 'documents/' . Str::uuid() . '.pdf',
            'checksum' => hash('sha256', Str::random(100)),
            'file_size_bytes' => fake()->numberBetween(10000, 10000000),
            'page_count' => fake()->numberBetween(1, 100),
            'document_type' => fake()->randomElement([
                ContractDocument::TYPE_RFP,
                ContractDocument::TYPE_RFQ,
                ContractDocument::TYPE_IFB,
                ContractDocument::TYPE_AMENDMENT,
                ContractDocument::TYPE_ATTACHMENT,
            ]),
            'status' => ContractDocument::STATUS_PENDING,
            'cui_detected' => false,
            'cui_categories' => null,
            'uploaded_at' => now(),
        ];
    }

    /**
     * Indicate that the document belongs to an opportunity.
     */
    public function forOpportunity(?SamOpportunity $opportunity = null): static
    {
        return $this->state(fn (array $attributes) => [
            'sam_opportunity_id' => $opportunity?->id ?? SamOpportunity::factory(),
        ]);
    }

    /**
     * Indicate that the document was uploaded by an admin.
     */
    public function uploadedBy(?Admin $admin = null): static
    {
        return $this->state(fn (array $attributes) => [
            'uploaded_by' => $admin?->id ?? Admin::factory(),
        ]);
    }

    /**
     * Indicate that the document has been parsed.
     */
    public function parsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractDocument::STATUS_PARSED,
        ]);
    }

    /**
     * Indicate that parsing failed.
     */
    public function failed(?string $message = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractDocument::STATUS_FAILED,
            'error_message' => $message ?? 'Parsing failed: ' . fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the document is being processed.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractDocument::STATUS_PROCESSING,
        ]);
    }

    /**
     * Indicate that the document contains CUI.
     */
    public function withCui(array $categories = ['SP-PROCURE']): static
    {
        return $this->state(fn (array $attributes) => [
            'cui_detected' => true,
            'cui_categories' => $categories,
        ]);
    }

    /**
     * Create as a Word document.
     */
    public function docx(): static
    {
        return $this->state(fn (array $attributes) => [
            'original_filename' => fake()->words(3, true) . '.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'storage_path' => 'documents/' . Str::uuid() . '.docx',
        ]);
    }

    /**
     * Create as an Excel document.
     */
    public function xlsx(): static
    {
        return $this->state(fn (array $attributes) => [
            'original_filename' => fake()->words(3, true) . '.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'storage_path' => 'documents/' . Str::uuid() . '.xlsx',
        ]);
    }

    /**
     * Create as an amendment.
     */
    public function amendment(?ContractDocument $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => ContractDocument::TYPE_AMENDMENT,
            'parent_document_id' => $parent?->id,
        ]);
    }
}
