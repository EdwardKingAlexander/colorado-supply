<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToSourcingCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;

class NsnSourcingResponse extends Model
{
    use AppendOnly, BelongsToSourcingCompany, HasFactory, LogsActivity;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['received_on' => 'date', 'distributors' => 'array', 'lead_time_days' => 'integer'];
    }

    public function sourcingCase(): BelongsTo
    {
        return $this->belongsTo(NsnSourcingCase::class, 'nsn_sourcing_case_id');
    }

    public const CHANNELS = ['email' => 'Email', 'phone' => 'Phone call', 'web_form' => 'Web form', 'document' => 'Quote or document', 'other' => 'Other'];

    public const SELLS_DIRECT = ['yes' => 'Yes', 'no' => 'No', 'unknown' => 'Not stated'];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(NsnSourcingInquiry::class, 'nsn_sourcing_inquiry_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_response_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(NsnSourcingFile::class, 'nsn_sourcing_response_id');
    }
}
