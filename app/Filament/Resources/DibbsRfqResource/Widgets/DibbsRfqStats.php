<?php

namespace App\Filament\Resources\DibbsRfqResource\Widgets;

use App\Models\DibbsFile;
use App\Models\DibbsRfq;
use App\Models\DibbsRfqTracking;
use App\Services\Dibbs\DibbsHealth;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * At-a-glance DIBBS pipeline, limited to RFQs in our product codes.
 */
class DibbsRfqStats extends BaseWidget
{
    protected function getStats(): array
    {
        $latest = DibbsFile::where('type', DibbsFile::TYPE_INDEX)
            ->where('status', DibbsFile::STATUS_IMPORTED)
            ->orderByDesc('posting_date')
            ->first();

        $ours = fn () => DibbsRfq::query()->inOurProductCodes();

        $newInLatest = $latest ? $ours()->where('first_seen_file_id', $latest->id)->count() : 0;

        $closingSoon = $ours()
            ->whereBetween('return_by_date', [today()->toDateString(), today()->addDays(3)->toDateString()])
            ->where(fn (Builder $q) => $q
                ->whereDoesntHave('tracking')
                ->orWhereHas('tracking', fn (Builder $t) => $t->whereIn('status', ['new', 'reviewing'])))
            ->count();

        $byStatus = DibbsRfqTracking::query()
            ->where('company_id', DibbsRfqTracking::currentCompanyId())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $health = app(DibbsHealth::class)->status();

        return [
            Stat::make('New in latest file', number_format($newInLatest))
                ->description($health['stale']
                    ? ($latest ? 'Feed is '.$health['business_days_behind'].' business days behind: check Upload DIBBS File' : 'No DIBBS file imported yet')
                    : 'Posted '.$latest->posting_date->format('M j').', in our product codes')
                ->color($health['stale'] ? 'danger' : null)
                ->icon($health['stale'] ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-inbox-arrow-down'),

            Stat::make('Closing in 3 days', number_format($closingSoon))
                ->description('Not yet reviewed or still reviewing')
                ->color($closingSoon > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-clock'),

            Stat::make('Quoting', number_format($byStatus['quoting'] ?? 0))
                ->description('Being priced')
                ->color('warning')
                ->icon('heroicon-o-calculator'),

            Stat::make('Quoted, awaiting award', number_format($byStatus['quoted'] ?? 0))
                ->description(($byStatus['won'] ?? 0).' won · '.($byStatus['lost'] ?? 0).' lost')
                ->color('primary')
                ->icon('heroicon-o-paper-airplane'),
        ];
    }
}
