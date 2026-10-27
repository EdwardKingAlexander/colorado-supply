<?php

namespace App\Filament\Resources\DibbsRfqResource\Pages;

use App\Filament\Resources\DibbsRfqResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewDibbsRfq extends ViewRecord
{
    protected static string $resource = DibbsRfqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DibbsRfqResource::addToWorksheetAction(),
            DibbsRfqResource::setStatusAction(),
            DibbsRfqResource::assignAction(),
            DibbsRfqResource::noteAction(),
            DibbsRfqResource::addToCatalogAction(),
            Action::make('openInDibbs')
                ->label('Open in DIBBS')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => $this->getRecord()->dibbsRecordUrl(), shouldOpenInNewTab: true),
        ];
    }
}
