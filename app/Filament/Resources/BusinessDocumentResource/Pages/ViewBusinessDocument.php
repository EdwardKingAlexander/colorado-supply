<?php

namespace App\Filament\Resources\BusinessDocumentResource\Pages;

use App\Filament\Resources\BusinessDocumentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBusinessDocument extends ViewRecord
{
    protected static string $resource = BusinessDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
