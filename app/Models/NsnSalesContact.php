<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSourcingCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class NsnSalesContact extends Model
{
    use BelongsToSourcingCompany, HasFactory, LogsActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['source_on' => 'date'];
    }

    public function sourcingCase(): BelongsTo
    {
        return $this->belongsTo(NsnSourcingCase::class, 'nsn_sourcing_case_id');
    }

    public function vendorContact(): BelongsTo
    {
        return $this->belongsTo(VendorContact::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function label(): string
    {
        return collect([$this->contact_name, $this->company_name, $this->email, $this->phone])->filter()->implode(' · ') ?: 'Contact #'.$this->id;
    }
}
