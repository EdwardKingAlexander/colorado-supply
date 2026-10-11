<?php

namespace App\Filament\Resources\NsnSourcingCases\Pages;

use App\Filament\Pages\FetchNsnData;
use App\Filament\Resources\NsnSourcingCases\NsnSourcingCaseResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListNsnSourcingCases extends ListRecords
{
    protected static string $resource = NsnSourcingCaseResource::class;

    public function getSubheading(): ?string
    {
        return 'Start a case from a reference in PUB LOG Lookup.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('lookup')->label('PUB LOG Lookup')->icon('heroicon-o-magnifying-glass')->url(FetchNsnData::getUrl()),
        ];
    }
}
