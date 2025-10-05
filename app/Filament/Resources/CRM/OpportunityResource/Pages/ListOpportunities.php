<?php

namespace App\Filament\Resources\CRM\OpportunityResource\Pages;

use App\Filament\Resources\CRM\OpportunityResource;
use App\Filament\Widgets\CRM\PipelineKpis;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOpportunities extends ListRecords
{
    protected static string $resource = OpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PipelineKpis::class,
        ];
    }
}
