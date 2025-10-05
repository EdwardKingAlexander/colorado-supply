<?php

namespace App\Filament\Resources\CRM\PipelineResource\Pages;

use App\Filament\Resources\CRM\PipelineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPipeline extends EditRecord
{
    protected static string $resource = PipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
