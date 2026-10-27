<?php

namespace App\Filament\Resources\DibbsRfqResource\Pages;

use App\Filament\Pages\DibbsUpload;
use App\Filament\Resources\DibbsRfqResource;
use App\Filament\Resources\DibbsRfqResource\Widgets\DibbsRfqStats;
use App\Services\Dibbs\DibbsRfqMatcher;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDibbsRfqs extends ListRecords
{
    protected static string $resource = DibbsRfqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rebuildMatches')
                ->label('Rebuild matches')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $counts = app(DibbsRfqMatcher::class)->rebuild();

                    Notification::make()
                        ->title('Matches rebuilt')
                        ->body("{$counts['nsn_catalog']} in your catalog, {$counts['approved_source']} where you buy from an approved source.")
                        ->success()
                        ->send();
                }),
            Action::make('upload')
                ->label('Upload DIBBS file')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->url(DibbsUpload::getUrl()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DibbsRfqStats::class,
        ];
    }
}
