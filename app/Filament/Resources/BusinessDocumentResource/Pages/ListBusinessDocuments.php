<?php

namespace App\Filament\Resources\BusinessDocumentResource\Pages;

use App\Filament\Resources\BusinessDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBusinessDocuments extends ListRecords
{
    protected static string $resource = BusinessDocumentResource::class;

    protected string $view = 'filament.resources.business-documents.pages.list-business-documents';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
