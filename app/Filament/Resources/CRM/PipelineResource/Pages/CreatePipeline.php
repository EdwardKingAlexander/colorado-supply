<?php

namespace App\Filament\Resources\CRM\PipelineResource\Pages;

use App\Filament\Resources\CRM\PipelineResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePipeline extends CreateRecord
{
    protected static string $resource = PipelineResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
