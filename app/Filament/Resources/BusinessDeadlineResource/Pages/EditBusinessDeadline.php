<?php

namespace App\Filament\Resources\BusinessDeadlineResource\Pages;

use App\Filament\Resources\BusinessDeadlineResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBusinessDeadline extends EditRecord
{
    protected static string $resource = BusinessDeadlineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
