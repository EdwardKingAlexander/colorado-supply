<?php

namespace App\Models;

use App\Jobs\RebuildDibbsMatchesJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manufacturer extends Model
{
    protected $fillable = [
        'name',
        'cage_code',
    ];

    public function milSpecParts(): HasMany
    {
        return $this->hasMany(MilSpecPart::class);
    }

    /**
     * Keep DIBBS RFQ matches current when the cage code changes
     * (ai/modules/dibbs-rfq-tracking, Phase 4).
     */
    protected static function booted(): void
    {
        static::saved(function (Manufacturer $model) {
            if (($model->wasRecentlyCreated && $model->cage_code) || $model->wasChanged('cage_code')) {
                RebuildDibbsMatchesJob::request();
            }
        });

        static::deleted(fn () => RebuildDibbsMatchesJob::request());
    }
}
