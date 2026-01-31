<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessDocument extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'issue_date' => 'date',
            'expiration_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function deadlines(): HasMany
    {
        return $this->hasMany(BusinessDeadline::class, 'related_document_id');
    }

    public function isExpired(): bool
    {
        if (! $this->expiration_date) {
            return false;
        }

        return $this->expiration_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if (! $this->expiration_date) {
            return false;
        }

        return $this->expiration_date->isBetween(now(), now()->addDays($days));
    }

    public function daysUntilExpiration(): ?int
    {
        if (! $this->expiration_date) {
            return null;
        }

        return (int) now()->diffInDays($this->expiration_date, false);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => DocumentStatus::Expired]);
    }

    public function markAsPendingRenewal(): void
    {
        $this->update(['status' => DocumentStatus::PendingRenewal]);
    }

    public function markAsActive(): void
    {
        $this->update(['status' => DocumentStatus::Active]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', DocumentStatus::Active);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', DocumentStatus::Expired);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', DocumentStatus::Active)
            ->whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [now(), now()->addDays($days)]);
    }

    public function scopeOfType($query, DocumentType $type)
    {
        return $query->where('type', $type);
    }
}
