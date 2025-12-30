<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractDocumentRelationship extends Model
{
    use HasFactory;

    public const TYPE_AMENDS = 'amends';
    public const TYPE_SUPERSEDES = 'supersedes';
    public const TYPE_REFERENCES = 'references';
    public const TYPE_ATTACHMENT_OF = 'attachment_of';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'amendment_number' => 'integer',
        ];
    }

    /**
     * Get the parent document (the one being modified/referenced).
     */
    public function parentDocument(): BelongsTo
    {
        return $this->belongsTo(ContractDocument::class, 'parent_document_id');
    }

    /**
     * Get the child document (the one doing the modifying/referencing).
     */
    public function childDocument(): BelongsTo
    {
        return $this->belongsTo(ContractDocument::class, 'child_document_id');
    }

    /**
     * Get the admin who created this relationship.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Check if this is an amendment relationship.
     */
    public function isAmendment(): bool
    {
        return $this->relationship_type === self::TYPE_AMENDS;
    }

    /**
     * Check if this is a supersession relationship.
     */
    public function isSupersession(): bool
    {
        return $this->relationship_type === self::TYPE_SUPERSEDES;
    }

    /**
     * Check if this is a reference relationship.
     */
    public function isReference(): bool
    {
        return $this->relationship_type === self::TYPE_REFERENCES;
    }

    /**
     * Check if this is an attachment relationship.
     */
    public function isAttachment(): bool
    {
        return $this->relationship_type === self::TYPE_ATTACHMENT_OF;
    }

    /**
     * Get relationship type options for forms.
     */
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_AMENDS => 'Amends',
            self::TYPE_SUPERSEDES => 'Supersedes',
            self::TYPE_REFERENCES => 'References',
            self::TYPE_ATTACHMENT_OF => 'Attachment Of',
        ];
    }

    /**
     * Get a human-readable description of the relationship.
     */
    public function getDescriptionAttribute(): string
    {
        $childName = $this->childDocument?->original_filename ?? 'Unknown';
        $parentName = $this->parentDocument?->original_filename ?? 'Unknown';

        return match ($this->relationship_type) {
            self::TYPE_AMENDS => "{$childName} amends {$parentName}",
            self::TYPE_SUPERSEDES => "{$childName} supersedes {$parentName}",
            self::TYPE_REFERENCES => "{$childName} references {$parentName}",
            self::TYPE_ATTACHMENT_OF => "{$childName} is an attachment of {$parentName}",
            default => "{$childName} relates to {$parentName}",
        };
    }
}
