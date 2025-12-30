<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractDocumentAuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public const ACTION_UPLOADED = 'uploaded';
    public const ACTION_VIEWED = 'viewed';
    public const ACTION_DOWNLOADED = 'downloaded';
    public const ACTION_PARSED = 'parsed';
    public const ACTION_PARSE_FAILED = 'parse_failed';
    public const ACTION_REVIEWED = 'reviewed';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_REJECTED = 'rejected';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_RESTORED = 'restored';
    public const ACTION_LINKED = 'linked';
    public const ACTION_UNLINKED = 'unlinked';
    public const ACTION_CUI_DETECTED = 'cui_detected';
    public const ACTION_CUI_ACCESSED = 'cui_accessed';

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the document this log entry belongs to.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ContractDocument::class, 'contract_document_id');
    }

    /**
     * Get the admin who performed the action.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Create a new audit log entry.
     */
    public static function log(
        ContractDocument $document,
        string $action,
        ?array $metadata = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        return self::create([
            'contract_document_id' => $document->id,
            'admin_id' => auth()->guard('web')->id(),
            'action' => $action,
            'metadata' => $metadata,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get human-readable action description.
     */
    public function getActionDescriptionAttribute(): string
    {
        $adminName = $this->admin?->name ?? 'System';

        return match ($this->action) {
            self::ACTION_UPLOADED => "{$adminName} uploaded this document",
            self::ACTION_VIEWED => "{$adminName} viewed this document",
            self::ACTION_DOWNLOADED => "{$adminName} downloaded this document",
            self::ACTION_PARSED => "Document was parsed successfully",
            self::ACTION_PARSE_FAILED => "Document parsing failed",
            self::ACTION_REVIEWED => "{$adminName} reviewed extracted facts",
            self::ACTION_APPROVED => "{$adminName} approved extracted facts",
            self::ACTION_REJECTED => "{$adminName} rejected extracted facts",
            self::ACTION_UPDATED => "{$adminName} updated this document",
            self::ACTION_DELETED => "{$adminName} deleted this document",
            self::ACTION_RESTORED => "{$adminName} restored this document",
            self::ACTION_LINKED => "{$adminName} linked this document to another",
            self::ACTION_UNLINKED => "{$adminName} unlinked this document",
            self::ACTION_CUI_DETECTED => "CUI markings were detected",
            self::ACTION_CUI_ACCESSED => "{$adminName} accessed CUI-marked document",
            default => "{$adminName} performed action: {$this->action}",
        };
    }
}
