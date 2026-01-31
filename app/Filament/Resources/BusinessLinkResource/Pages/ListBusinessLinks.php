<?php

namespace App\Filament\Resources\BusinessLinkResource\Pages;

use App\Filament\Resources\BusinessLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBusinessLinks extends ListRecords
{
    protected static string $resource = BusinessLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
