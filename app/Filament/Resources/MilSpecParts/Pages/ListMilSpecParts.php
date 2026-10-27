<?php

namespace App\Filament\Resources\MilSpecParts\Pages;

use App\Filament\Resources\MilSpecParts\MilSpecPartResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMilSpecParts extends ListRecords
{
    protected static string $resource = MilSpecPartResource::class;

    protected string $view = 'filament.resources.mil-spec-parts.pages.list-mil-spec-parts';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import catalog')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->url(MilSpecPartResource::getUrl('import')),
            CreateAction::make(),
        ];
    }
}
