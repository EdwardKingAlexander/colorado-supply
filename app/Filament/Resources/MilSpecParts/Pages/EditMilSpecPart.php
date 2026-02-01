<?php

namespace App\Filament\Resources\MilSpecParts\Pages;

use App\Filament\Resources\MilSpecParts\MilSpecPartResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMilSpecPart extends EditRecord
{
    protected static string $resource = MilSpecPartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
