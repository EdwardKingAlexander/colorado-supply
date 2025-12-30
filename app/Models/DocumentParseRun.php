<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentParseRun extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const DRIVER_NATIVE_PDF = 'native_pdf';

    public const DRIVER_OCR_TESSERACT = 'ocr_tesseract';

    public const DRIVER_DOCX = 'docx';

    public const DRIVER_XLSX = 'xlsx';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'metrics' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the document this parse run belongs to.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ContractDocument::class, 'contract_document_id');
    }

    /**
     * Get artifacts produced by this parse run.
     */
    public function artifacts(): HasMany
    {
        return $this->hasMany(DocumentArtifact::class, 'parse_run_id');
    }

    /**
     * Mark this run as started.
     */
    public function markAsRunning(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark this run as completed.
     */
    public function markAsCompleted(array $metrics = []): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'finished_at' => now(),
            'metrics' => $metrics,
            'is_active' => true,
        ]);

        // Deactivate other parse runs for this document
        self::where('contract_document_id', $this->contract_document_id)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);
    }

    /**
     * Mark this run as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'finished_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Check if this run is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if this run is currently running.
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if this run completed successfully.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if this run failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Get duration in seconds.
     */
    public function getDurationInSeconds(): ?int
    {
        if (! $this->started_at || ! $this->finished_at) {
            return null;
        }

        return $this->finished_at->diffInSeconds($this->started_at);
    }

    /**
     * Get human-readable duration.
     */
    public function getFormattedDuration(): ?string
    {
        $seconds = $this->getDurationInSeconds();

        if ($seconds === null) {
            return null;
        }

        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return "{$minutes}m {$remainingSeconds}s";
    }

    /**
     * Get status options for forms.
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_RUNNING => 'Running',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    /**
     * Get driver options for forms.
     */
    public static function getDriverOptions(): array
    {
        return [
            self::DRIVER_NATIVE_PDF => 'Native PDF',
            self::DRIVER_OCR_TESSERACT => 'OCR (Tesseract)',
            self::DRIVER_DOCX => 'Word Document',
            self::DRIVER_XLSX => 'Excel Spreadsheet',
        ];
    }
}
