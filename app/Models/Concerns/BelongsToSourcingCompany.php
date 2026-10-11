<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\SourcingCompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

/**
 * Shared by every private sourcing table: company scoping, the case's
 * company copied onto children, and activity logging under "nsn-sourcing".
 */
trait BelongsToSourcingCompany
{
    public static function bootBelongsToSourcingCompany(): void
    {
        static::addGlobalScope(new SourcingCompanyScope);
        static::creating(function (self $model) {
            if (method_exists($model, 'sourcingCase') && $model->nsn_sourcing_case_id && ! $model->company_id) {
                $model->company_id = $model->sourcingCase()->withoutGlobalScopes()->value('company_id');
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('nsn-sourcing')->logAll()->logExcept(['updated_at', 'created_at'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->company_id = $this->company_id;
        $activity->properties = $activity->properties->put('company_id', $this->company_id);
    }
}
