<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSourcingCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class NsnSourcingInquiry extends Model
{
    use BelongsToSourcingCompany, HasFactory, LogsActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['contacted_at' => 'datetime'];
    }

    public function sourcingCase(): BelongsTo
    {
        return $this->belongsTo(NsnSourcingCase::class, 'nsn_sourcing_case_id');
    }

    public const CHANNELS = ['email' => 'Email', 'phone' => 'Phone', 'web_form' => 'Web form', 'other' => 'Other'];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(NsnSalesContact::class, 'nsn_sales_contact_id');
    }

    public function isContacted(): bool
    {
        return $this->contacted_at !== null;
    }
}
