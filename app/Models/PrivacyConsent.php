<?php

namespace App\Models;

use Database\Factories\PrivacyConsentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Consent receipt for demonstrable state-privacy-law compliance.
 *
 * Intentionally NOT company-scoped: consent belongs to a person (visitor
 * UUID from the cs_privacy_consent cookie), not a tenant.
 */
class PrivacyConsent extends Model
{
    /** @use HasFactory<PrivacyConsentFactory> */
    use HasFactory;

    protected $fillable = [
        'visitor_uuid',
        'user_id',
        'categories',
        'gpc_applied',
        'policy_version',
        'ip_hash',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'gpc_applied' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForVisitor(Builder $query, string $visitorUuid): Builder
    {
        return $query->where('visitor_uuid', $visitorUuid);
    }

    public static function latestForVisitor(string $visitorUuid): ?self
    {
        return static::forVisitor($visitorUuid)->latest('created_at')->latest('id')->first();
    }

    public function allowsCategory(string $category): bool
    {
        return in_array($category, $this->categories ?? [], true);
    }
}
