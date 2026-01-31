<?php

namespace App\Filament\Widgets;

use App\Enums\DocumentStatus;
use App\Models\BusinessDeadline;
use App\Models\BusinessDocument;
use App\Models\BusinessLink;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BusinessHubStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $activeDocuments = BusinessDocument::where('status', DocumentStatus::Active)->count();
        $expiringDocuments = BusinessDocument::expiringSoon(30)->count();

        $overdueDeadlines = BusinessDeadline::overdue()->count();
        $upcomingDeadlines = BusinessDeadline::upcoming(30)->count();

        $activeLinks = BusinessLink::active()->count();

        return [
            Stat::make('Active Documents', $activeDocuments)
                ->description($expiringDocuments > 0 ? "{$expiringDocuments} expiring soon" : 'All documents current')
                ->descriptionIcon($expiringDocuments > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($expiringDocuments > 0 ? 'warning' : 'success'),

            Stat::make('Upcoming Deadlines', $upcomingDeadlines)
                ->description($overdueDeadlines > 0 ? "{$overdueDeadlines} overdue" : 'No overdue items')
                ->descriptionIcon($overdueDeadlines > 0 ? 'heroicon-o-exclamation-circle' : 'heroicon-o-check-circle')
                ->color($overdueDeadlines > 0 ? 'danger' : 'success'),

            Stat::make('Quick Links', $activeLinks)
                ->description('Active resources')
                ->descriptionIcon('heroicon-o-link')
                ->color('primary'),
        ];
    }
}
