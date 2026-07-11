<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Activity
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
