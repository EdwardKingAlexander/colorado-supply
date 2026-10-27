<?php

namespace App\Services\Dibbs;

use App\Models\DibbsRfqMatch;
use App\Models\DibbsRfqTracking;
use App\Models\Manufacturer;
use App\Models\MilSpecPart;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rebuilds the company's DIBBS RFQ matches from scratch (ai/modules/dibbs-rfq-tracking, Phase 4).
 *
 * - nsn_catalog (100): the RFQ's NSN is a part in our catalog. The catalog
 *   stores NSNs dashed (XXXX-XX-XXX-XXXX); DIBBS uses the 13 plain characters.
 * - approved_source (70): DLA's approved source file lists, for the RFQ's NSN,
 *   a CAGE code belonging to a manufacturer or supplier we have. For
 *   approved-source-only buys (most AMSC codes) that is what makes a
 *   distributor's offer acceptable.
 *
 * The weaker "in our product codes" signal is not stored: it would add a row
 * for most RFQs and is already a filter on the RFQ list.
 *
 * Idempotent: rebuilding twice yields the same rows.
 */
class DibbsRfqMatcher
{
    protected const CHUNK = 500;

    /**
     * @return array{nsn_catalog: int, approved_source: int}
     */
    public function rebuild(?int $companyId = null): array
    {
        $companyId ??= DibbsRfqTracking::currentCompanyId();
        $now = now();

        $catalog = DB::table('dibbs_rfqs as r')
            ->join('mil_spec_parts as p', DB::raw("replace(p.nsn, '-', '')"), '=', 'r.nsn')
            ->select('r.id as rfq_id', 'p.id as matchable_id')
            ->get()
            ->map(fn ($row) => [
                'reason' => DibbsRfqMatch::REASON_CATALOG,
                'dibbs_rfq_id' => $row->rfq_id,
                'matchable_type' => MilSpecPart::class,
                'matchable_id' => $row->matchable_id,
                'cage_code' => null,
                'part_number' => null,
            ]);

        $approved = collect([Manufacturer::class => 'manufacturers', Supplier::class => 'suppliers'])
            ->flatMap(fn (string $table, string $class) => DB::table('dibbs_rfqs as r')
                ->join('dibbs_approved_sources as s', 's.nsn', '=', 'r.nsn')
                ->join("{$table} as m", DB::raw('upper(m.cage_code)'), '=', 's.cage_code')
                ->whereNotNull('m.cage_code')
                ->select('r.id as rfq_id', 'm.id as matchable_id', 's.cage_code', 's.part_number')
                ->get()
                ->map(fn ($row) => [
                    'reason' => DibbsRfqMatch::REASON_APPROVED_SOURCE,
                    'dibbs_rfq_id' => $row->rfq_id,
                    'matchable_type' => $class,
                    'matchable_id' => $row->matchable_id,
                    'cage_code' => $row->cage_code,
                    'part_number' => $row->part_number,
                ]));

        // One row per (rfq, reason, matchable): an approved source with several
        // approved part numbers for the same NSN keeps the first.
        $rows = $catalog->concat($approved)
            ->unique(fn (array $r) => $r['dibbs_rfq_id'].'|'.$r['reason'].'|'.$r['matchable_type'].'|'.$r['matchable_id'])
            ->map(fn (array $r) => $r + [
                'company_id' => $companyId,
                'score' => DibbsRfqMatch::SCORES[$r['reason']],
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values();

        DB::transaction(function () use ($companyId, $rows) {
            DibbsRfqMatch::withoutGlobalScopes()->where('company_id', $companyId)->delete();

            foreach ($rows->chunk(self::CHUNK) as $chunk) {
                DibbsRfqMatch::insert($chunk->all());
            }
        });

        $counts = [
            DibbsRfqMatch::REASON_CATALOG => $rows->where('reason', DibbsRfqMatch::REASON_CATALOG)->count(),
            DibbsRfqMatch::REASON_APPROVED_SOURCE => $rows->where('reason', DibbsRfqMatch::REASON_APPROVED_SOURCE)->count(),
        ];

        Log::info('DIBBS RFQ matches rebuilt', $counts + ['company_id' => $companyId]);

        return $counts;
    }
}
