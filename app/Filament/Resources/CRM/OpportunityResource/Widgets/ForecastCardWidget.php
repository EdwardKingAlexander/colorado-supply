<?php

namespace App\Filament\Resources\CRM\OpportunityResource\Widgets;

use App\Models\Opportunity;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class ForecastCardWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (!$this->record instanceof Opportunity) {
            return [];
        }

        $opportunity = $this->record;

        $probability = $opportunity->probability_effective;
        $forecast = $opportunity->forecast_amount;
        $amount = $opportunity->amount;

        return [
            Stat::make('Opportunity Value', '$' . number_format($amount, 2))
                ->description('Total deal value')
                ->color('info')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Probability', $probability . '%')
                ->description($opportunity->probability_override ? 'Custom override' : 'Stage default')
                ->color('warning')
                ->icon('heroicon-o-chart-bar'),

            Stat::make('Weighted Forecast', '$' . number_format($forecast, 2))
                ->description('Value × Probability')
                ->color('success')
                ->icon('heroicon-o-presentation-chart-line'),
        ];
    }
}
