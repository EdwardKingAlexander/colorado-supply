<?php

namespace App\Filament\Resources\CRM\QuoteResource\Widgets;

use App\Models\Quote;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuoteStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $query = Quote::query();

        // Apply same policy filtering
        if (!auth()->user()->hasRole('super_admin')) {
            $allowedRoles = ['admins', 'sales_managers', 'sales_reps', 'super_admins'];
            if (!auth()->user()->hasAnyRole($allowedRoles)) {
                $query->whereRaw('1 = 0'); // No access
            }
        }

        return [
            Stat::make('Draft Quotes', $query->clone()->where('status', 'draft')->count())
                ->description('Pending quotes')
                ->color('gray'),

            Stat::make('Sent Quotes', $query->clone()->where('status', 'sent')->count())
                ->description('Awaiting customer response')
                ->color('primary'),

            Stat::make('Open Pipeline', '$' . number_format($query->clone()->whereIn('status', ['draft', 'sent'])->sum('grand_total'), 2))
                ->description('Total value of active quotes')
                ->color('warning'),

            Stat::make('Ordered', $query->clone()->where('status', 'ordered')->count())
                ->description('Converted to orders')
                ->color('success'),
        ];
    }
}
