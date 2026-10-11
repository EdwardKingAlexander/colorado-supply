<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSourcingCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A company's effort to confirm whether one PUB LOG reference (NSN + part +
 * CAGE) is still produced and who sells it. Catalog values are a snapshot of
 * the release the case was opened from; they are never "verified" by this.
 */
class NsnSourcingCase extends Model
{
    use BelongsToSourcingCompany, HasFactory, LogsActivity;

    public const STATUSES = [
        'open' => 'Open',
        'awaiting_response' => 'Awaiting response',
        'answered' => 'Answered',
        'closed' => 'Closed',
    ];

    public const STATUS_COLORS = ['open' => 'info', 'awaiting_response' => 'warning', 'answered' => 'success', 'closed' => 'gray'];

    /** States a response can report. "unknown" = the reply did not say. */
    public const REPORTED_STATES = [
        'unknown' => 'Not stated',
        'current' => 'Currently produced',
        'discontinued' => 'Discontinued',
        'replaced' => 'Replaced by another part',
    ];

    /** States a case can show. Only recorded responses move it off "unknown". */
    public const PRODUCTION_STATES = [
        'unknown' => 'Unknown',
        'current' => 'Current',
        'discontinued' => 'Discontinued',
        'replaced' => 'Replaced',
        'conflicting' => 'Conflicting',
    ];

    public const PRODUCTION_COLORS = ['unknown' => 'gray', 'current' => 'success', 'discontinued' => 'danger', 'replaced' => 'warning', 'conflicting' => 'danger'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'follow_up_on' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public static function openKey(string $niin, string $partSearchKey, string $cage): string
    {
        return hash('sha256', $niin."\0".$partSearchKey."\0".$cage);
    }

    public function formattedNsn(): string
    {
        return substr($this->nsn, 0, 4).'-'.substr($this->nsn, 4, 2).'-'.substr($this->nsn, 6, 3).'-'.substr($this->nsn, 9);
    }

    public function isOpen(): bool
    {
        return $this->status !== 'closed';
    }

    public function openedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function milSpecPart(): BelongsTo
    {
        return $this->belongsTo(MilSpecPart::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(NsnCageVerification::class)->latest('id');
    }

    public function latestVerification(): HasOne
    {
        return $this->hasOne(NsnCageVerification::class)->latestOfMany();
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(NsnSalesContact::class)->latest('id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(NsnSourcingInquiry::class)->latest('id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(NsnSourcingResponse::class)->latest('received_on')->latest('id');
    }

    public function latestResponse(): HasOne
    {
        return $this->hasOne(NsnSourcingResponse::class)->ofMany(['received_on' => 'max', 'id' => 'max']);
    }
}
