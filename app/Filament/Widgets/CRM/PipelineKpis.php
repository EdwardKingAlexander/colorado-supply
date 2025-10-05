<?php

namespace App\Filament\Widgets\CRM;

use App\Models\Opportunity;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PipelineKpis extends BaseWidget
{
    protected function getStats(): array
    {
        $openOpportunities = Opportunity::where('status', 'open')->get();

        $totalPipeline = $openOpportunities->sum('amount');
        $totalForecast = $openOpportunities->sum(fn($opp) => $opp->forecast_amount);

        $expectedCloseThisMonth = Opportunity::where('status', 'open')
            ->whereMonth('expected_close_date', now()->month)
            ->whereYear('expected_close_date', now()->year)
            ->sum('amount');

        // Win rate last 90 days
        $closedLast90Days = Opportunity::whereIn('status', ['won', 'lost'])
            ->where('closed_at', '>=', now()->subDays(90))
            ->get();

        $wonCount = $closedLast90Days->where('status', 'won')->count();
        $totalClosed = $closedLast90Days->count();
        $winRate = $totalClosed > 0 ? round(($wonCount / $totalClosed) * 100, 1) : 0;

        return [
            Stat::make('Total Pipeline', '$' . number_format($totalPipeline, 2))
                ->description($openOpportunities->count() . ' open opportunities')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('info')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Weighted Forecast', '$' . number_format($totalForecast, 2))
                ->description('Based on stage probabilities')
                ->descriptionIcon('heroicon-o-calculator')
                ->color('success'),

            Stat::make('Expected Close This Month', '$' . number_format($expectedCloseThisMonth, 2))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-o-calendar')
                ->color('warning'),

            Stat::make('Win Rate (90d)', $winRate . '%')
                ->description($wonCount . ' won of ' . $totalClosed . ' closed')
                ->descriptionIcon('heroicon-o-trophy')
                ->color($winRate >= 50 ? 'success' : 'danger'),
        ];
    }
}
