<?php

namespace App\Models;

use App\Enums\DeadlineCategory;
use App\Enums\RecurrenceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessDeadline extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'category' => DeadlineCategory::class,
            'recurrence' => RecurrenceType::class,
            'due_date' => 'date',
            'recurrence_rule' => 'array',
            'reminder_days' => 'array',
            'last_reminder_sent_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function relatedDocument(): BelongsTo
    {
        return $this->belongsTo(BusinessDocument::class, 'related_document_id');
    }

    public function isOverdue(): bool
    {
        return ! $this->isCompleted() && $this->due_date->isPast();
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isDueSoon(int $days = 14): bool
    {
        if ($this->isCompleted()) {
            return false;
        }

        return $this->due_date->isBetween(now(), now()->addDays($days));
    }

    public function daysUntilDue(): int
    {
        return (int) now()->diffInDays($this->due_date, false);
    }

    public function markAsCompleted(): void
    {
        $this->update(['completed_at' => now()]);
    }

    public function markAsIncomplete(): void
    {
        $this->update(['completed_at' => null]);
    }

    public function shouldSendReminder(): bool
    {
        if ($this->isCompleted() || ! $this->reminder_days) {
            return false;
        }

        $daysUntilDue = $this->daysUntilDue();

        foreach ($this->reminder_days as $reminderDay) {
            if ($daysUntilDue === (int) $reminderDay) {
                return true;
            }
        }

        return false;
    }

    public function recordReminderSent(): void
    {
        $this->update(['last_reminder_sent_at' => now()]);
    }

    public function createNextRecurrence(): ?self
    {
        if ($this->recurrence === RecurrenceType::Once) {
            return null;
        }

        $nextDueDate = match ($this->recurrence) {
            RecurrenceType::Monthly => $this->due_date->addMonth(),
            RecurrenceType::Quarterly => $this->due_date->addMonths(3),
            RecurrenceType::Annually => $this->due_date->addYear(),
            default => null,
        };

        if (! $nextDueDate) {
            return null;
        }

        return self::create([
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'due_date' => $nextDueDate,
            'recurrence' => $this->recurrence,
            'recurrence_rule' => $this->recurrence_rule,
            'reminder_days' => $this->reminder_days,
            'related_document_id' => $this->related_document_id,
            'external_url' => $this->external_url,
        ]);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNull('completed_at')
            ->where('due_date', '<', now());
    }

    public function scopeUpcoming($query, int $days = 30)
    {
        return $query->whereNull('completed_at')
            ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeIncomplete($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeOfCategory($query, DeadlineCategory $category)
    {
        return $query->where('category', $category);
    }
}
