<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A reason an RFQ fits the company's catalog. Rebuilt by DibbsRfqMatcher.
 */
class DibbsRfqMatch extends Model
{
    public const REASON_CATALOG = 'nsn_catalog';

    public const REASON_APPROVED_SOURCE = 'approved_source';

    public const SCORES = [
        self::REASON_CATALOG => 100,
        self::REASON_APPROVED_SOURCE => 70,
    ];

    public const LABELS = [
        self::REASON_CATALOG => 'In catalog',
        self::REASON_APPROVED_SOURCE => 'Approved source',
    ];

    protected $guarded = [];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(DibbsRfq::class, 'dibbs_rfq_id');
    }

    public function matchable(): MorphTo
    {
        return $this->morphTo();
    }
}
