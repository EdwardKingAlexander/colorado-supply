<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToSourcingCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class NsnCageVerification extends Model
{
    use AppendOnly, BelongsToSourcingCompany, HasFactory, LogsActivity;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['verified_on' => 'date'];
    }

    public function sourcingCase(): BelongsTo
    {
        return $this->belongsTo(NsnSourcingCase::class, 'nsn_sourcing_case_id');
    }

    public const OUTCOMES = [
        'verified' => 'Verified in CAGE Search',
        'conflicting' => 'Conflicts with PUB LOG',
        'inactive' => 'Inactive or obsolete',
        'unresolved' => 'Could not resolve',
    ];

    public const OUTCOME_COLORS = ['verified' => 'success', 'conflicting' => 'danger', 'inactive' => 'warning', 'unresolved' => 'gray'];
}
