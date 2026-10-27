<?php

namespace App\Models;

use App\Jobs\RebuildDibbsMatchesJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'cage_code',
        'contact_info',
        'website',
    ];

    public function procurementHistories(): HasMany
    {
        return $this->hasMany(ProcurementHistory::class);
    }

    public function milSpecParts(): BelongsToMany
    {
        return $this->belongsToMany(MilSpecPart::class)
            ->withPivot('supplier_part_number');
    }

    /**
     * Keep DIBBS RFQ matches current when the cage code changes
     * (ai/modules/dibbs-rfq-tracking, Phase 4).
     */
    protected static function booted(): void
    {
        static::saved(function (Supplier $model) {
            if (($model->wasRecentlyCreated && $model->cage_code) || $model->wasChanged('cage_code')) {
                RebuildDibbsMatchesJob::request();
            }
        });

        static::deleted(fn () => RebuildDibbsMatchesJob::request());
    }
}
