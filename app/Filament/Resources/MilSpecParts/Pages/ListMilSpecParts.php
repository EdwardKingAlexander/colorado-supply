<?php

namespace App\Filament\Resources\MilSpecParts\Pages;

use App\Filament\Resources\MilSpecParts\MilSpecPartResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMilSpecParts extends ListRecords
{
    protected static string $resource = MilSpecPartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
