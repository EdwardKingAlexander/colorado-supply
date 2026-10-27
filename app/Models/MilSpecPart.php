<?php

namespace App\Models;

use App\Jobs\RebuildDibbsMatchesJob;
use Database\Factories\MilSpecPartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MilSpecPart extends Model
{
    /** @use HasFactory<MilSpecPartFactory> */
    use HasFactory;

    protected $fillable = [
        'nsn',
        'description',
        'manufacturer_part_number',
        'manufacturer_id',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function procurementHistories(): HasMany
    {
        return $this->hasMany(ProcurementHistory::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)
            ->withPivot('supplier_part_number');
    }

    /**
     * Keep DIBBS RFQ matches current when the nsn changes
     * (ai/modules/dibbs-rfq-tracking, Phase 4).
     */
    protected static function booted(): void
    {
        static::saved(function (MilSpecPart $model) {
            if ($model->wasRecentlyCreated || $model->wasChanged('nsn')) {
                RebuildDibbsMatchesJob::request();
            }
        });

        static::deleted(fn () => RebuildDibbsMatchesJob::request());
    }
}
