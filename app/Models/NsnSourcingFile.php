<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToSourcingCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class NsnSourcingFile extends Model
{
    use AppendOnly, BelongsToSourcingCompany, HasFactory, LogsActivity;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(NsnSourcingResponse::class, 'nsn_sourcing_response_id');
    }
}
