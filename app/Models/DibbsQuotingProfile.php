<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * The company's standing DIBBS quote representations (ai/modules/dibbs-rfq-tracking, Phase 6).
 *
 * Every field is a legal representation or certification made on each quote.
 * The app never fills them in: a person enters them and confirms they are
 * true; any change clears the confirmation until re-confirmed.
 *
 * Code lists are DLA's (BatchFormat.aspx, last updated 30-APR-2024).
 */
class DibbsQuotingProfile extends Model
{
    use LogsActivity;

    /** Column 13: Small Business and Other Contractor Representations. */
    public const SMALL_BUSINESS_CODES = [
        'A' => 'A – Large Business / Other Business',
        'B' => 'B – Small Business',
        'C' => 'C – Nonprofit Institution',
        'E' => 'E – Educational Institution (other than HBCU or Minority)',
        'F' => 'F – Historically Black College or University (HBCU)',
        'G' => 'G – JWOD Participating Nonprofit Agency',
        'M' => 'M – Small Disadvantaged Business',
        'P' => 'P – Minority Institution (other than HBCU)',
        'X' => 'X – Intragovernmental',
    ];

    /** Column 21: Affirmative Action Compliance. */
    public const AFFIRMATIVE_ACTION_CODES = [
        'Y6' => 'Y6 – Developed and on file',
        'N6' => 'N6 – Not developed and not on file',
        'NH' => 'NH – No previous contracts subject to requirements',
        'NA' => 'NA – Not applicable',
    ];

    /** Column 22: Previous Contracts and Compliance Reports. */
    public const PREVIOUS_CONTRACTS_CODES = [
        'Y4' => 'Y4 – Participated and filed',
        'Y5' => 'Y5 – Participated and not filed',
        'N4' => 'N4 – Not participated',
        'NA' => 'NA – Not applicable',
    ];

    /** Column 23: Alternate Disputes Resolution (DLAD 5452.233-9001). */
    public const ADR_CODES = [
        'A' => 'A – Agree to use alternate disputes resolution',
        'B' => 'B – Do not agree to use alternate disputes resolution',
    ];

    /** Column 120: Child Labor Certification (FAR 52.222-18). Y forces a bid with exception: not supported. */
    public const CHILD_LABOR_CODES = [
        'N' => 'N – No',
        'U' => 'U – May supply, but not aware of any such use of child labor based on good-faith effort',
    ];

    /** Column 65: Hazardous Material Identification (FAR 52.223-3). */
    public const HAZARDOUS_MATERIAL_CODES = [
        'N' => 'N – No',
        'Y' => 'Y – Yes',
    ];

    /** Column 67: Material Requirements (FAR 52.211-5). */
    public const MATERIAL_CODES = [
        '0' => '0 – New (not used, reconditioned, remanufactured or surplus)',
        '1' => '1 – Other than new (used)',
        '2' => '2 – Reconditioned',
        '3' => '3 – Remanufactured',
        '4' => '4 – Unused former Government surplus',
    ];

    /** Column 102: status of the Quote For CAGE. */
    public const MANUFACTURER_DEALER_CODES = [
        'DD' => 'DD – Dealer',
        'MM' => 'MM – Manufacturer',
        'QD' => 'QD – Qualified Supplier List dealer',
        'QM' => 'QM – Qualified Supplier List manufacturer',
    ];

    /** Fields that are representations; editing any clears the confirmation. */
    public const REPRESENTATION_FIELDS = [
        'quoter_cage', 'quote_for_cage', 'small_business_code', 'affirmative_action_code',
        'previous_contracts_code', 'adr_code', 'child_labor_code', 'default_hazardous_material',
        'default_material_code', 'default_manufacturer_dealer',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public static function current(): self
    {
        return static::firstOrNew(['company_id' => DibbsRfqTracking::currentCompanyId()]);
    }

    public function isComplete(): bool
    {
        return collect(self::REPRESENTATION_FIELDS)->every(fn ($field) => filled($this->{$field}));
    }

    public function isConfirmed(): bool
    {
        return $this->isComplete() && $this->confirmed_at !== null;
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'confirmed_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('dibbs')
            ->logOnly([...self::REPRESENTATION_FIELDS, 'confirmed_by', 'confirmed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->company_id = $this->company_id;
    }
}
