<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Services\Categories\CategoryImportCommitter;
use App\Services\Categories\CategoryImportPlan;
use App\Services\Categories\CategoryImportPlanner;
use App\Services\Categories\CategoryImportReader;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportCategories extends Page
{
    protected static string $resource = CategoryResource::class;

    protected string $view = 'filament.resources.category.import-categories';

    protected static ?string $title = 'Import Categories from CSV';

    /** Max rows accepted in a single upload. */
    protected const MAX_ROWS = 5000;

    public ?array $formData = ['file' => null];

    /** Preview summary shown after a dry-run analyze; null before analyzing. */
    public ?array $preview = null;

    /**
     * Absolute path to the uploaded file. Persisted across the analyze ->
     * confirm round-trip (same Livewire instance) and used as a test seam.
     */
    public ?string $uploadedAbsolutePath = null;

    public function mount(): void
    {
        $this->formData = ['file' => null];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Upload')
                    ->description('Upload a .csv or .xlsx with columns: name (required), parent, description, image. Parent references another row by its name; blank means top level.')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Category file')
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->disk('local')
                            ->directory('category-imports')
                            ->visibility('private')
                            ->storeFiles()
                            ->required(),
                    ]),
            ])
            ->statePath('formData');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadTemplate')
                ->label('Download sample CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action('downloadTemplate'),
        ];
    }

    public function getFormActions(): array
    {
        return [
            Action::make('analyze')
                ->label('Preview import')
                ->icon(Heroicon::OutlinedMagnifyingGlass)
                ->action('analyze'),
        ];
    }

    public function analyze(): void
    {
        $path = $this->resolveUploadedPath();
        if ($path === null) {
            return;
        }

        $rows = app(CategoryImportReader::class)->read($path);

        if (count($rows) > self::MAX_ROWS) {
            Notification::make()
                ->title('File too large')
                ->body('This file has '.count($rows).' rows; the limit is '.self::MAX_ROWS.'.')
                ->danger()
                ->send();

            return;
        }

        $plan = app(CategoryImportPlanner::class)->plan($rows);
        $this->preview = $this->summarize($plan);

        Notification::make()
            ->title('Preview ready')
            ->body($plan->newCount().' new, '.$plan->reuseCount().' existing, '.$plan->errorCount().' error(s).')
            ->info()
            ->send();
    }

    public function confirm(): void
    {
        $path = $this->uploadedAbsolutePath;
        if ($path === null || ! is_file($path)) {
            Notification::make()->title('Nothing to import')->body('Upload and preview a file first.')->danger()->send();

            return;
        }

        $rows = app(CategoryImportReader::class)->read($path);
        $plan = app(CategoryImportPlanner::class)->plan($rows);

        if (! $plan->isValid()) {
            $this->preview = $this->summarize($plan);
            Notification::make()
                ->title('Import blocked')
                ->body('Fix the '.$plan->errorCount().' row error(s) shown below, then re-upload.')
                ->danger()
                ->send();

            return;
        }

        $result = app(CategoryImportCommitter::class)->commit($plan);

        $this->reset('preview', 'uploadedAbsolutePath');
        $this->formData = ['file' => null];

        Notification::make()
            ->title('Import complete')
            ->body($result->createdCount.' created, '.$result->reusedCount.' reused.')
            ->success()
            ->send();

        $this->redirect(CategoryResource::getUrl('index'));
    }

    public function downloadTemplate(): StreamedResponse
    {
        $rows = [
            ['name', 'parent', 'description', 'image'],
            ['Abrasives', '', 'Top-level category', ''],
            ['Sanding', 'Abrasives', '', ''],
            ['Belts', 'Sanding', 'Sanding belts', ''],
            ['Discs', 'Sanding', '', ''],
            ['Grinding', 'Abrasives', '', ''],
            ['Wheels', 'Grinding', '', ''],
        ];

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'category-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Resolve the uploaded file to an absolute path, caching it for confirm().
     */
    protected function resolveUploadedPath(): ?string
    {
        if ($this->uploadedAbsolutePath !== null && is_file($this->uploadedAbsolutePath)) {
            return $this->uploadedAbsolutePath;
        }

        $data = $this->form->getState();
        $stored = $data['file'] ?? null;
        if (is_array($stored)) {
            $stored = reset($stored);
        }

        if (! $stored) {
            Notification::make()->title('No file')->body('Please choose a file to upload.')->danger()->send();

            return null;
        }

        $absolute = Storage::disk('local')->path($stored);
        $this->uploadedAbsolutePath = $absolute;

        return $absolute;
    }

    protected function summarize(CategoryImportPlan $plan): array
    {
        return [
            'newCount' => $plan->newCount(),
            'reuseCount' => $plan->reuseCount(),
            'errorCount' => $plan->errorCount(),
            'isValid' => $plan->isValid(),
            'errors' => $plan->errors,
            'newPaths' => array_values(array_map(
                fn ($n) => $n->path,
                array_filter($plan->nodes, fn ($n) => $n->isCreate()),
            )),
        ];
    }
}
