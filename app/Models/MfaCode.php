<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MfaCode extends Model
{
    protected $fillable = [
        'user_id',
        'code_hash',
        'purpose',
        'expires_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Codes that are still usable: not consumed and not expired.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('consumed_at')->where('expires_at', '>', now());
    }

    public function scopeForPurpose(Builder $query, string $purpose): Builder
    {
        return $query->where('purpose', $purpose);
    }
}
