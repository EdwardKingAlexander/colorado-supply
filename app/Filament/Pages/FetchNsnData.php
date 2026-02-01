<?php

namespace App\Filament\Pages;

use App\Jobs\FetchNsnDataJob;
use App\Services\NsnLookupService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class FetchNsnData extends Page
{
    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCube;

    protected string $view = 'filament.pages.fetch-nsn-data';

    protected static UnitEnum|string|null $navigationGroup = 'NSN Procurement';

    protected static ?string $title = 'Fetch NSN Data';

    protected static ?int $navigationSort = 1;

    public ?array $formData = [];

    public ?array $result = null;

    public function mount(): void
    {
        $this->formData = [
            'nsn' => '',
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('NSN Lookup')
                    ->description('Enter an NSN to fetch manufacturer, supplier, and procurement data from external sources.')
                    ->schema([
                        TextInput::make('nsn')
                            ->label('National Stock Number (NSN)')
                            ->required()
                            ->placeholder('XXXX-XX-XXX-XXXX or 13 digits')
                            ->helperText('Enter the NSN in format XXXX-XX-XXX-XXXX (with dashes) or as 13 consecutive digits.')
                            ->maxLength(20)
                            ->autofocus(),
                    ]),
            ])
            ->statePath('formData');
    }

    public function getFormActions(): array
    {
        return [
            Action::make('fetchNow')
                ->label('Fetch NSN Data')
                ->icon(Heroicon::OutlinedMagnifyingGlass)
                ->action('fetchNsnData')
                ->color('primary'),
            Action::make('fetchInBackground')
                ->label('Queue Fetch')
                ->icon(Heroicon::OutlinedClock)
                ->action('queueFetchNsnData')
                ->color('gray'),
        ];
    }

    public function fetchNsnData(): void
    {
        $nsn = trim($this->formData['nsn'] ?? '');

        if (empty($nsn)) {
            Notification::make()
                ->title('Error')
                ->body('Please enter an NSN.')
                ->danger()
                ->send();

            return;
        }

        Log::info('FetchNsnData page - Starting immediate fetch', [
            'nsn' => $nsn,
            'user_id' => auth()->id(),
        ]);

        try {
            $service = app(NsnLookupService::class);
            $milSpecPart = $service->fetchAndPersistNsnData($nsn);

            if ($milSpecPart) {
                $this->result = [
                    'success' => true,
                    'message' => "Successfully fetched and saved data for NSN: {$milSpecPart->nsn}",
                    'mil_spec_part_id' => $milSpecPart->id,
                    'nsn' => $milSpecPart->nsn,
                    'description' => $milSpecPart->description,
                    'manufacturer' => $milSpecPart->manufacturer?->name,
                    'error' => null,
                ];

                Notification::make()
                    ->title('Success')
                    ->body("NSN data fetched and saved: {$milSpecPart->nsn}. See results below for details.")
                    ->success()
                    ->send();
            } else {
                $this->result = [
                    'success' => false,
                    'message' => 'No data found for the provided NSN. The NSN may be invalid or not found in external sources.',
                    'error' => 'No data returned from lookup service.',
                ];

                Notification::make()
                    ->title('Not Found')
                    ->body('No data found for the provided NSN.')
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('FetchNsnData page - Fetch failed', [
                'nsn' => $nsn,
                'error' => $e->getMessage(),
            ]);

            $this->result = [
                'success' => false,
                'message' => 'An error occurred while fetching NSN data.',
                'error' => $e->getMessage(),
            ];

            Notification::make()
                ->title('Error')
                ->body('Failed to fetch NSN data: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function queueFetchNsnData(): void
    {
        $nsn = trim($this->formData['nsn'] ?? '');

        if (empty($nsn)) {
            Notification::make()
                ->title('Error')
                ->body('Please enter an NSN.')
                ->danger()
                ->send();

            return;
        }

        Log::info('FetchNsnData page - Dispatching background job', [
            'nsn' => $nsn,
            'user_id' => auth()->id(),
        ]);

        try {
            FetchNsnDataJob::dispatch($nsn);

            $this->result = [
                'success' => true,
                'message' => "NSN fetch job has been queued for: {$nsn}. Check the Mil-Spec Parts list for results.",
                'error' => null,
            ];

            Notification::make()
                ->title('Job Queued')
                ->body("NSN fetch for {$nsn} has been queued. Results will appear in the Mil-Spec Parts list when ready.")
                ->info()
                ->send();
        } catch (\Exception $e) {
            Log::error('FetchNsnData page - Queue dispatch failed', [
                'nsn' => $nsn,
                'error' => $e->getMessage(),
            ]);

            $this->result = [
                'success' => false,
                'message' => 'Failed to queue the fetch job.',
                'error' => $e->getMessage(),
            ];

            Notification::make()
                ->title('Error')
                ->body('Failed to queue fetch job: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}
