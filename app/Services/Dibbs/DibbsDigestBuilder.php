<?php

namespace App\Services\Dibbs;

use App\Models\DibbsFile;
use App\Models\DibbsRfq;
use App\Models\DibbsRfqMatch;
use App\Models\DibbsRfqTracking;
use Illuminate\Database\Eloquent\Builder;

/**
 * Assembles the morning DIBBS digest (ai/modules/dibbs-rfq-tracking, Phase 5).
 *
 * Sections, strongest first; each RFQ appears once:
 * - catalog:        new RFQs for NSNs in our catalog
 * - approved:       new RFQs where we buy from an approved source
 * - product_codes:  other new RFQs in our product codes (if min_match allows)
 * - closing:        open RFQs closing within N days that we're reviewing or
 *                   quoting, or that match the catalog and nobody has touched
 *
 * "New" means first seen in the given posting file. Closed and no-bid RFQs
 * never appear.
 */
class DibbsDigestBuilder
{
    public const SECTIONS = [
        'catalog' => 'In your catalog',
        'approved' => 'You buy from an approved source',
        'product_codes' => 'In your product codes',
        'closing' => 'Closing soon',
    ];

    /**
     * @param  DibbsFile|null  $file  posting file whose new RFQs to report; null = closing-soon only
     * @return array{posting_date: ?string, new_file: bool, sections: array<string, array{title: string, total: int, items: array<int, array<string, mixed>>}>, is_empty: bool}
     */
    public function build(?DibbsFile $file): array
    {
        $limit = max(1, (int) config('dibbs.digest.section_limit', 25));
        $minMatch = config('dibbs.digest.min_match', 'product_codes');
        $shown = [];
        $sections = [];

        if ($file) {
            $new = fn () => $this->eligible()->where('first_seen_file_id', $file->id);

            $sections['catalog'] = $this->section('catalog',
                $new()->whereHas('matches', fn (Builder $m) => $m->where('reason', DibbsRfqMatch::REASON_CATALOG)), $limit, $shown);

            if (in_array($minMatch, ['approved_source', 'product_codes'], true)) {
                $sections['approved'] = $this->section('approved',
                    $new()->whereHas('matches', fn (Builder $m) => $m->where('reason', DibbsRfqMatch::REASON_APPROVED_SOURCE)), $limit, $shown);
            }

            if ($minMatch === 'product_codes') {
                $sections['product_codes'] = $this->section('product_codes',
                    $new()->inOurProductCodes(), $limit, $shown);
            }
        }

        // Closing soon: RFQs someone is working (reviewing/quoting), plus untouched
        // RFQs that match the catalog. Not every product-code RFQ: on real data
        // (2026-10-04) that was ~4,300 RFQs in a 3-day window, too many to act on.
        $days = max(0, (int) config('dibbs.digest.closing_within_days', 3));
        $closing = $this->eligible()
            ->whereBetween('return_by_date', [today()->toDateString(), today()->addDays($days)->toDateString()])
            ->where(fn (Builder $q) => $q
                ->whereHas('tracking', fn (Builder $t) => $t->whereIn('status', ['reviewing', 'quoting']))
                ->orWhere(fn (Builder $untouched) => $untouched
                    ->where(fn (Builder $n) => $n->whereDoesntHave('tracking')->orWhereHas('tracking', fn (Builder $t) => $t->where('status', 'new')))
                    ->where(fn (Builder $m) => $this->limitToMinMatch($m, $minMatch === 'product_codes' ? 'approved_source' : $minMatch))));

        $sections['closing'] = $this->section('closing', $closing, $limit, $shown);

        $sections = array_filter($sections, fn (array $s) => $s['total'] > 0);

        return [
            'posting_date' => $file?->posting_date?->toDateString(),
            'new_file' => $file !== null,
            'sections' => $sections,
            'is_empty' => $sections === [],
        ];
    }

    /**
     * Open RFQs nobody has ruled out.
     */
    protected function eligible(): Builder
    {
        return DibbsRfq::query()
            ->stillOpen()
            ->whereDoesntHave('tracking', fn (Builder $t) => $t->where('status', 'no_bid'));
    }

    protected function limitToMinMatch(Builder $query, string $minMatch): Builder
    {
        return match ($minMatch) {
            'catalog' => $query->whereHas('matches', fn (Builder $m) => $m->where('reason', DibbsRfqMatch::REASON_CATALOG)),
            'approved_source' => $query->whereHas('matches'),
            default => $query->where(fn (Builder $q) => $q->whereHas('matches')->orWhere(fn (Builder $p) => $p->inOurProductCodes())),
        };
    }

    /**
     * @param  array<int, true>  $shown  RFQ ids already listed in a stronger section
     */
    protected function section(string $key, Builder $query, int $limit, array &$shown): array
    {
        $query->whereNotIn('id', array_keys($shown));

        $total = (clone $query)->count();
        $rfqs = $query->with(['tracking', 'matches'])
            ->orderBy('return_by_date')
            ->limit($limit)
            ->get();

        foreach ($rfqs as $rfq) {
            $shown[$rfq->id] = true;
        }

        return [
            'title' => self::SECTIONS[$key],
            'total' => $total,
            'items' => $rfqs->map(fn (DibbsRfq $rfq) => [
                'solicitation_number' => $rfq->solicitation_number,
                'nsn' => $rfq->formattedNsn(),
                'nomenclature' => $rfq->nomenclature,
                'quantity' => trim($rfq->quantity.' '.$rfq->unit_of_issue),
                'return_by' => $rfq->return_by_date?->format('M j'),
                'set_aside' => $rfq->set_aside && $rfq->set_aside !== 'N' ? (DibbsRfq::SET_ASIDES[$rfq->set_aside] ?? $rfq->set_aside) : null,
                'competition' => $rfq->isOpenCompetition() ? 'Open' : 'Approved sources',
                'status' => DibbsRfqTracking::STATUSES[$rfq->trackingStatus()],
                'admin_url' => route('filament.admin.resources.dibbs-rfqs.view', ['record' => $rfq->id]),
                'dibbs_url' => $rfq->dibbsRecordUrl(),
            ])->all(),
        ];
    }
}
