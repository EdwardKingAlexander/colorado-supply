<?php

namespace App\Filament\Resources\BusinessLinkResource\Pages;

use App\Filament\Resources\BusinessLinkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBusinessLink extends EditRecord
{
    protected static string $resource = BusinessLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
