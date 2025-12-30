<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ContractDocument extends Model
{
    use HasFactory, SoftDeletes;

    public const DISK = 'contract_documents';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PARSED = 'parsed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ARCHIVED = 'archived';

    public const TYPE_RFP = 'rfp';
    public const TYPE_RFQ = 'rfq';
    public const TYPE_IFB = 'ifb';
    public const TYPE_AMENDMENT = 'amendment';
    public const TYPE_ATTACHMENT = 'attachment';
    public const TYPE_OTHER = 'other';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'cui_detected' => 'boolean',
            'cui_categories' => 'array',
            'uploaded_at' => 'datetime',
            'page_count' => 'integer',
            'file_size_bytes' => 'integer',
        ];
    }

    /**
     * Get the parent document (for versions).
     */
    public function parentDocument(): BelongsTo
    {
        return $this->belongsTo(ContractDocument::class, 'parent_document_id');
    }

    /**
     * Get child versions of this document.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ContractDocument::class, 'parent_document_id');
    }

    /**
     * Get the SAM opportunity this document belongs to.
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(SamOpportunity::class, 'sam_opportunity_id');
    }

    /**
     * Get the admin who uploaded this document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'uploaded_by');
    }

    /**
     * Get audit logs for this document.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(ContractDocumentAuditLog::class)->orderByDesc('created_at');
    }

    /**
     * Get all parse runs for this document.
     */
    public function parseRuns(): HasMany
    {
        return $this->hasMany(DocumentParseRun::class)->orderByDesc('created_at');
    }

    /**
     * Get the active/current parse run.
     */
    public function activeParseRun(): ?DocumentParseRun
    {
        return $this->parseRuns()->where('is_active', true)->first();
    }

    /**
     * Get the latest parse run regardless of status.
     */
    public function latestParseRun(): ?DocumentParseRun
    {
        return $this->parseRuns()->first();
    }

    /**
     * Get all artifacts for this document.
     */
    public function artifacts(): HasMany
    {
        return $this->hasMany(DocumentArtifact::class);
    }

    /**
     * Get extracted text artifact from active parse run.
     */
    public function getExtractedText(): ?string
    {
        $artifact = $this->artifacts()
            ->where('artifact_type', DocumentArtifact::TYPE_EXTRACTED_TEXT)
            ->whereHas('parseRun', fn ($q) => $q->where('is_active', true))
            ->first();

        return $artifact?->getContent();
    }

    /**
     * Log an action on this document.
     */
    public function logAction(string $action, ?array $metadata = null, ?array $oldValues = null, ?array $newValues = null): ContractDocumentAuditLog
    {
        return ContractDocumentAuditLog::log($this, $action, $metadata, $oldValues, $newValues);
    }

    /**
     * Get relationships where this document is the parent.
     */
    public function childRelationships(): HasMany
    {
        return $this->hasMany(ContractDocumentRelationship::class, 'parent_document_id');
    }

    /**
     * Get relationships where this document is the child.
     */
    public function parentRelationships(): HasMany
    {
        return $this->hasMany(ContractDocumentRelationship::class, 'child_document_id');
    }

    /**
     * Get all amendments to this document.
     */
    public function amendments(): HasMany
    {
        return $this->childRelationships()
            ->where('relationship_type', ContractDocumentRelationship::TYPE_AMENDS)
            ->orderBy('amendment_number');
    }

    /**
     * Get the document this one amends (if it's an amendment).
     */
    public function amendsDocument(): ?ContractDocument
    {
        $relationship = $this->parentRelationships()
            ->where('relationship_type', ContractDocumentRelationship::TYPE_AMENDS)
            ->first();

        return $relationship?->parentDocument;
    }

    /**
     * Get all attachments to this document.
     */
    public function attachments(): HasMany
    {
        return $this->childRelationships()
            ->where('relationship_type', ContractDocumentRelationship::TYPE_ATTACHMENT_OF);
    }

    /**
     * Get the document this is attached to (if it's an attachment).
     */
    public function attachedTo(): ?ContractDocument
    {
        $relationship = $this->parentRelationships()
            ->where('relationship_type', ContractDocumentRelationship::TYPE_ATTACHMENT_OF)
            ->first();

        return $relationship?->parentDocument;
    }

    /**
     * Check if this document has amendments.
     */
    public function hasAmendments(): bool
    {
        return $this->amendments()->exists();
    }

    /**
     * Check if this document is an amendment.
     */
    public function isAmendment(): bool
    {
        return $this->parentRelationships()
            ->where('relationship_type', ContractDocumentRelationship::TYPE_AMENDS)
            ->exists();
    }

    /**
     * Get the amendment chain (all amendments in order).
     */
    public function getAmendmentChain(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->amendments()
            ->with('childDocument')
            ->get()
            ->map(fn ($rel) => $rel->childDocument)
            ->filter();
    }

    /**
     * Get the full storage path for the document.
     */
    public function getFullPathAttribute(): string
    {
        return Storage::disk($this->storage_disk)->path($this->storage_path);
    }

    /**
     * Get the download URL for the document.
     */
    public function getDownloadUrlAttribute(): ?string
    {
        if (Storage::disk($this->storage_disk)->exists($this->storage_path)) {
            return Storage::disk($this->storage_disk)->temporaryUrl(
                $this->storage_path,
                now()->addMinutes(30)
            );
        }

        return null;
    }

    /**
     * Get human-readable file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size_bytes;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    /**
     * Check if the document has CUI markings.
     */
    public function hasCui(): bool
    {
        return $this->cui_detected === true;
    }

    /**
     * Check if the document is pending processing.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the document is currently being processed.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if the document has been parsed successfully.
     */
    public function isParsed(): bool
    {
        return $this->status === self::STATUS_PARSED;
    }

    /**
     * Check if parsing failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if this is a version of another document.
     */
    public function isVersion(): bool
    {
        return $this->parent_document_id !== null;
    }

    /**
     * Check if this document has versions.
     */
    public function hasVersions(): bool
    {
        return $this->versions()->exists();
    }

    /**
     * Get the latest version of this document.
     */
    public function getLatestVersion(): ContractDocument
    {
        return $this->versions()->latest()->first() ?? $this;
    }

    /**
     * Get document type options for forms.
     */
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_RFP => 'Request for Proposal (RFP)',
            self::TYPE_RFQ => 'Request for Quote (RFQ)',
            self::TYPE_IFB => 'Invitation for Bid (IFB)',
            self::TYPE_AMENDMENT => 'Amendment',
            self::TYPE_ATTACHMENT => 'Attachment',
            self::TYPE_OTHER => 'Other',
        ];
    }

    /**
     * Get status options for forms.
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_PARSED => 'Parsed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }
}
