<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * The company's pursuit of one DIBBS RFQ: status pipeline, owner, notes, and
 * (Phase 6) quoted price and delivery. Every change is activity-logged.
 */
class DibbsRfqTracking extends Model
{
    use LogsActivity;

    public const STATUSES = [
        'new' => 'New',
        'reviewing' => 'Reviewing',
        'quoting' => 'Quoting',
        'quoted' => 'Quoted',
        'no_bid' => 'No bid',
        'won' => 'Won',
        'lost' => 'Lost',
    ];

    public const STATUS_COLORS = [
        'new' => 'gray',
        'reviewing' => 'info',
        'quoting' => 'warning',
        'quoted' => 'primary',
        'no_bid' => 'gray',
        'won' => 'success',
        'lost' => 'danger',
    ];

    protected $guarded = [];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:4',
            'delivery_days' => 'integer',
            'quoted_at' => 'datetime',
            'exported_at' => 'datetime',
            'quote_snapshot' => 'array',
        ];
    }

    /**
     * Admins have no company; they act for the home company (the app's single
     * operating company). Users with a company act for theirs.
     */
    public static function currentCompanyId(): ?int
    {
        return auth()->user()?->company_id ?? Company::query()->orderBy('id')->value('id');
    }

    /**
     * The current company's tracking row for an RFQ, created on first use.
     */
    public static function for(DibbsRfq $rfq): self
    {
        return static::firstOrCreate(['company_id' => static::currentCompanyId(), 'dibbs_rfq_id' => $rfq->id]);
    }

    /**
     * Move to the quote worksheet, pre-filling what can be inferred safely:
     * DLA's required delivery days, and, when we buy from one of the RFQ's
     * approved sources, that exact approved CAGE + part number. Never the price.
     */
    public function startQuoting(): self
    {
        $rfq = $this->rfq;
        $template = $rfq->quoteTemplates()->orderBy('row_index')->first();
        $approved = $rfq->matches()->where('reason', DibbsRfqMatch::REASON_APPROVED_SOURCE)->whereNotNull('part_number')->first();

        $this->fill(array_filter([
            'status' => 'quoting',
            'delivery_days' => $this->delivery_days ?? $template?->delivery_days,
            'part_offered_code' => $this->part_offered_code ?? ($approved ? '1' : null),
            'part_offered_cage' => $this->part_offered_cage ?? $approved?->cage_code,
            'part_offered_number' => $this->part_offered_number ?? $approved?->part_number,
            'manufacturing_source_cage' => $this->manufacturing_source_cage ?? $approved?->cage_code,
        ], fn ($value) => $value !== null))->save();

        return $this;
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(DibbsRfq::class, 'dibbs_rfq_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('dibbs')
            ->logOnly([
                'status', 'assigned_to', 'notes', 'unit_price', 'delivery_days',
                'manufacturer_dealer', 'manufacturing_source_cage', 'part_offered_code', 'part_offered_cage',
                'part_offered_number', 'quality_code', 'hazardous_material', 'material_code', 'exported_at', 'export_reference',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->company_id = $this->company_id;
        $activity->properties = $activity->properties
            ->put('company_id', $this->company_id)
            ->put('solicitation_number', $this->rfq?->solicitation_number);
    }
}
