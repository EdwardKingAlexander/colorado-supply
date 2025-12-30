<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentArtifact extends Model
{
    // Artifact types
    public const TYPE_EXTRACTED_TEXT = 'extracted_text';

    public const TYPE_STRUCTURED_JSON = 'structured_json';

    public const TYPE_PAGE_MAP = 'page_map';

    public const TYPE_SECTION_CHUNKS = 'section_chunks';

    public const TYPE_RENDERED_HTML = 'rendered_html';

    public const TYPE_DISTILLED_MARKDOWN = 'distilled_markdown';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'file_size_bytes' => 'integer',
        ];
    }

    /**
     * Get the document this artifact belongs to.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ContractDocument::class, 'contract_document_id');
    }

    /**
     * Get the parse run that created this artifact.
     */
    public function parseRun(): BelongsTo
    {
        return $this->belongsTo(DocumentParseRun::class, 'parse_run_id');
    }

    /**
     * Get the content of this artifact.
     * Returns from DB if stored there, otherwise reads from storage.
     */
    public function getContent(): ?string
    {
        // If content is stored in DB, return it directly
        if ($this->content !== null) {
            return $this->content;
        }

        // Otherwise, read from storage
        if ($this->storage_path && Storage::disk($this->storage_disk)->exists($this->storage_path)) {
            return Storage::disk($this->storage_disk)->get($this->storage_path);
        }

        return null;
    }

    /**
     * Get structured content (for JSON artifacts).
     */
    public function getStructuredContent(): ?array
    {
        $content = $this->getContent();

        if (! $content) {
            return null;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Store content for this artifact.
     */
    public function storeContent(string $content, bool $storeInDatabase = false): void
    {
        if ($storeInDatabase) {
            $this->update([
                'content' => $content,
                'file_size_bytes' => strlen($content),
                'checksum' => hash('sha256', $content),
            ]);
        } else {
            Storage::disk($this->storage_disk)->put($this->storage_path, $content);

            $this->update([
                'file_size_bytes' => strlen($content),
                'checksum' => hash('sha256', $content),
            ]);
        }
    }

    /**
     * Get human-readable artifact type.
     */
    public function getTypeLabel(): string
    {
        return match ($this->artifact_type) {
            self::TYPE_EXTRACTED_TEXT => 'Extracted Text',
            self::TYPE_STRUCTURED_JSON => 'Structured Data',
            self::TYPE_PAGE_MAP => 'Page Mapping',
            self::TYPE_SECTION_CHUNKS => 'Section Chunks',
            self::TYPE_RENDERED_HTML => 'Rendered HTML',
            self::TYPE_DISTILLED_MARKDOWN => 'Contract Brief',
            default => ucfirst(str_replace('_', ' ', $this->artifact_type)),
        };
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size_bytes ?? 0;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    /**
     * Get artifact type options for forms.
     */
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_EXTRACTED_TEXT => 'Extracted Text',
            self::TYPE_STRUCTURED_JSON => 'Structured Data',
            self::TYPE_PAGE_MAP => 'Page Mapping',
            self::TYPE_SECTION_CHUNKS => 'Section Chunks',
            self::TYPE_RENDERED_HTML => 'Rendered HTML',
            self::TYPE_DISTILLED_MARKDOWN => 'Contract Brief',
        ];
    }
}
