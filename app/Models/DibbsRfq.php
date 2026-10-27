<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * One DIBBS RFQ line (solicitation + purchase request), from a daily index file.
 */
class DibbsRfq extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * AMSC codes open to any source (DFARS PGI 217.75): G full government data,
     * Z commercial/off-the-shelf, L low-dollar buy, O not yet assigned. Every
     * other code limits the buy to approved sources.
     */
    public const OPEN_AMSC = ['G', 'Z', 'L', 'O'];

    /** Small-business set-aside codes from DLA's index file layout. */
    public const SET_ASIDES = [
        'Y' => 'Small business',
        'H' => 'HUBZone',
        'R' => 'SDVOSB',
        'L' => 'WOSB',
        'A' => '8(a)',
        'E' => 'EDWOSB',
        'N' => 'Unrestricted',
    ];

    /** AMSC meanings (DFARS PGI 217.75), short form for the UI. */
    public const AMSC_LABELS = [
        'A' => 'Data rights questionable',
        'B' => 'Source control drawing',
        'C' => 'Engineering source approval',
        'D' => 'Data unavailable',
        'G' => 'Full and open (complete data)',
        'H' => 'Insufficient data',
        'K' => 'Class 1 castings/forgings',
        'L' => 'Low-dollar buy',
        'M' => 'Master tooling',
        'N' => 'Special test / ultra-precision',
        'O' => 'Not yet assigned',
        'P' => 'No data rights',
        'Q' => 'Inadequate data (12+ months)',
        'R' => 'Government lacks data rights',
        'S' => 'Militarily sensitive',
        'T' => 'Qualified products list (QPL)',
        'U' => 'Breakout not cost-effective',
        'V' => 'High reliability',
        'Y' => 'Unstable design',
        'Z' => 'Commercial / off-the-shelf',
    ];

    protected function casts(): array
    {
        return [
            'return_by_date' => 'date',
            'quantity' => 'integer',
            'set_aside_percent' => 'integer',
            'automated_award' => 'boolean',
        ];
    }

    public function firstSeenFile(): BelongsTo
    {
        return $this->belongsTo(DibbsFile::class, 'first_seen_file_id');
    }

    public function approvedSources(): HasMany
    {
        return $this->hasMany(DibbsApprovedSource::class, 'nsn', 'nsn');
    }

    public function quoteTemplates(): HasMany
    {
        return $this->hasMany(DibbsQuoteTemplate::class);
    }

    public function isOpenCompetition(): bool
    {
        return in_array($this->amsc, self::OPEN_AMSC, true);
    }

    /**
     * The current company's tracking row, if anyone has acted on this RFQ.
     */
    public function tracking(): HasOne
    {
        return $this->hasOne(DibbsRfqTracking::class)->where('company_id', DibbsRfqTracking::currentCompanyId());
    }

    /**
     * Every company's tracking rows and matches (unscoped). For maintenance,
     * e.g. pruning must respect anyone's activity, not just the current company's.
     */
    public function allTrackings(): HasMany
    {
        return $this->hasMany(DibbsRfqTracking::class)->withoutGlobalScopes();
    }

    public function allMatches(): HasMany
    {
        return $this->hasMany(DibbsRfqMatch::class)->withoutGlobalScopes();
    }

    public function trackingStatus(): string
    {
        return $this->tracking?->status ?? 'new';
    }

    /**
     * Why this RFQ fits the current company's catalog (DibbsRfqMatcher).
     */
    public function matches(): HasMany
    {
        return $this->hasMany(DibbsRfqMatch::class)->where('company_id', DibbsRfqTracking::currentCompanyId());
    }

    /**
     * Strongest match reason, or null (uses the loaded relation when present).
     */
    public function bestMatchReason(): ?string
    {
        return $this->matches->sortByDesc('score')->first()?->reason;
    }

    /**
     * Our catalog parts carrying this NSN (the catalog stores NSNs dashed).
     */
    public function catalogParts(): Collection
    {
        return MilSpecPart::query()
            ->whereRaw("replace(nsn, '-', '') = ?", [$this->nsn])
            ->with(['manufacturer', 'suppliers', 'procurementHistories' => fn ($q) => $q->latest('acquisition_date')->with('supplier')])
            ->get();
    }

    /*
    | DIBBS links, verified 2026-10-04 against live pages (findings.md). The
    | solicitation PDF sits in a folder named for the solicitation's last character.
    */

    public function dibbsRecordUrl(): string
    {
        return 'https://www.dibbs.bsm.dla.mil/rfq/rfqrec.aspx?sn='.rawurlencode($this->solicitation_number);
    }

    public function dibbsNsnHistoryUrl(): string
    {
        return 'https://www.dibbs.bsm.dla.mil/RFQ/RFQNsn.aspx?value='.rawurlencode($this->nsn);
    }

    public function dibbsQuoteFormUrl(): string
    {
        return 'https://www.dibbs.bsm.dla.mil/RA/Quote/QuoteFrm.aspx?sn='.rawurlencode($this->solicitation_number);
    }

    public function solicitationPdfUrl(): string
    {
        return 'https://dibbs2.bsm.dla.mil/Downloads/RFQ/'.substr($this->solicitation_number, -1).'/'.rawurlencode($this->solicitation_number).'.PDF';
    }

    public function formattedNsn(): string
    {
        return $this->item_type === '1' && strlen($this->nsn) === 13
            ? substr($this->nsn, 0, 4).'-'.substr($this->nsn, 4, 2).'-'.substr($this->nsn, 6, 3).'-'.substr($this->nsn, 9)
            : $this->nsn;
    }

    /**
     * RFQs in the product codes we sell: the same lists SAM PSC discovery uses
     * (4-character PSCs match the FSC; 2-character groups match its first two digits).
     */
    public function scopeInOurProductCodes(Builder $query): Builder
    {
        $codes = config('sam_opportunities.psc_codes', []);
        $groups = array_merge(config('sam_opportunities.psc_groups', []), config('sam_opportunities.psc_groups_optional', []));

        return $query->where(fn (Builder $q) => $q
            ->whereIn('fsc', $codes)
            ->orWhereIn(DB::raw('substr(fsc, 1, 2)'), $groups));
    }

    public function scopeStillOpen(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('return_by_date')->orWhere('return_by_date', '>=', today()->toDateString()));
    }

    public function scopeOpenCompetition(Builder $query): Builder
    {
        return $query->whereIn('amsc', self::OPEN_AMSC);
    }

    public function scopeApprovedSourceOnly(Builder $query): Builder
    {
        return $query->whereNotNull('amsc')->whereNotIn('amsc', self::OPEN_AMSC);
    }
}
