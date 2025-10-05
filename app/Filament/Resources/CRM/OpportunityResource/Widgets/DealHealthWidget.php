<?php

namespace App\Filament\Resources\CRM\OpportunityResource\Widgets;

use App\Models\Opportunity;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class DealHealthWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (!$this->record instanceof Opportunity) {
            return [];
        }

        $opportunity = $this->record;

        // Next activity due
        $nextActivity = $opportunity->activities()
            ->whereNull('done_at')
            ->whereNotNull('due_at')
            ->where('due_at', '>=', now())
            ->orderBy('due_at')
            ->first();

        $nextActivityText = $nextActivity
            ? Carbon::parse($nextActivity->due_at)->diffForHumans()
            : 'No upcoming activities';

        $nextActivityColor = $nextActivity ? 'success' : 'warning';

        // Days in current stage
        $daysInStage = $opportunity->updated_at->diffInDays(now());
        $daysInStageColor = match (true) {
            $daysInStage > 30 => 'danger',
            $daysInStage > 14 => 'warning',
            default => 'success',
        };

        // Days since last activity
        $lastActivity = $opportunity->activities()->latest('created_at')->first();
        $daysSinceActivity = $lastActivity
            ? Carbon::parse($lastActivity->created_at)->diffInDays(now())
            : $opportunity->created_at->diffInDays(now());

        $inactiveColor = match (true) {
            $daysSinceActivity > 14 => 'danger',
            $daysSinceActivity > 7 => 'warning',
            default => 'success',
        };

        return [
            Stat::make('Next Activity', $nextActivityText)
                ->description('Upcoming task or event')
                ->color($nextActivityColor)
                ->icon('heroicon-o-calendar'),

            Stat::make('Days in Stage', $daysInStage . ' days')
                ->description('Time in ' . $opportunity->stage->name)
                ->color($daysInStageColor)
                ->icon('heroicon-o-clock'),

            Stat::make('Days Since Activity', $daysSinceActivity . ' days')
                ->description('Last engagement')
                ->color($inactiveColor)
                ->icon('heroicon-o-chat-bubble-left-right'),
        ];
    }
}
