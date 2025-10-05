<?php

namespace App\Filament\Resources\CRM\OpportunityResource\Pages;

use App\Filament\Resources\CRM\OpportunityResource;
use App\Filament\Resources\CRM\OpportunityResource\Widgets\ActivityTimelineWidget;
use App\Filament\Resources\CRM\OpportunityResource\Widgets\DealHealthWidget;
use App\Filament\Resources\CRM\OpportunityResource\Widgets\ForecastCardWidget;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOpportunity extends ViewRecord
{
    protected static string $resource = OpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DealHealthWidget::class,
            ForecastCardWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ActivityTimelineWidget::class,
        ];
    }
}
