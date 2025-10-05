<?php

namespace App\Filament\Resources\CRM\PipelineResource\Pages;

use App\Filament\Resources\CRM\PipelineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPipelines extends ListRecords
{
    protected static string $resource = PipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
