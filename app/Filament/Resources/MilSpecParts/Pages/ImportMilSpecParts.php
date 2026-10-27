<?php

namespace App\Filament\Resources\MilSpecParts\Pages;

use App\Filament\Resources\MilSpecParts\MilSpecPartResource;
use App\Imports\HeadingRowsImport;
use App\Services\Catalog\MilSpecPartCatalogImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bulk-load the NSN parts catalog that DIBBS RFQ matching runs against
 * (ai/modules/dibbs-rfq-tracking, Phase 4.1). Preview first, then confirm.
 */
class ImportMilSpecParts extends Page
{
    protected static string $resource = MilSpecPartResource::class;

    protected string $view = 'filament.resources.mil-spec-parts.import-mil-spec-parts';

    protected static ?string $title = 'Import Parts Catalog';

    protected const MAX_ROWS = 10000;

    public ?array $formData = ['file' => null];

    /** Preview summary after analyze(); null before. */
    public ?array $preview = null;

    /** Uploaded file, kept for the analyze → confirm round trip. */
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
                    ->description('A .csv or .xlsx with columns: nsn, description, manufacturer_name, manufacturer_cage, manufacturer_part_number, supplier_name, supplier_cage, supplier_part_number. Only nsn, description and the manufacturer are required. Manufacturer and supplier CAGE codes drive DIBBS approved-source matching, so include them when you know them.')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Catalog file')
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->disk('local')
                            ->directory('catalog-imports')
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
                ->label('Download template')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action('downloadTemplate'),
        ];
    }

    public function analyze(): void
    {
        $rows = $this->readRows();

        if ($rows === null) {
            return;
        }

        if (count($rows) > self::MAX_ROWS) {
            Notification::make()->title('File too large')->body('This file has '.count($rows).' rows; the limit is '.self::MAX_ROWS.'.')->danger()->send();

            return;
        }

        $plan = app(MilSpecPartCatalogImporter::class)->analyze($rows);
        $this->preview = collect($plan)->except('rows')->all();

        Notification::make()
            ->title('Preview ready')
            ->body("{$plan['new_parts']} new, {$plan['updated_parts']} updated, ".count($plan['errors']).' error(s).')
            ->info()
            ->send();
    }

    public function confirm(): void
    {
        $rows = $this->readRows();

        if ($rows === null) {
            return;
        }

        $importer = app(MilSpecPartCatalogImporter::class);
        $plan = $importer->analyze($rows);

        if (! $plan['is_valid']) {
            $this->preview = collect($plan)->except('rows')->all();
            Notification::make()->title('Import blocked')->body('Fix the '.count($plan['errors']).' row error(s) shown below, then re-upload.')->danger()->send();

            return;
        }

        $result = $importer->commit($plan);

        $this->reset('preview', 'uploadedAbsolutePath');
        $this->formData = ['file' => null];

        Notification::make()
            ->title('Catalog imported')
            ->body("{$result['created']} created, {$result['updated']} updated. DIBBS matches: {$result['matches']['nsn_catalog']} catalog, {$result['matches']['approved_source']} approved source.")
            ->success()
            ->send();

        $this->redirect(MilSpecPartResource::getUrl('index'));
    }

    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            foreach (MilSpecPartCatalogImporter::templateRows() as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'parts-catalog-template.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    protected function readRows(): ?array
    {
        if ($this->uploadedAbsolutePath === null || ! is_file($this->uploadedAbsolutePath)) {
            $stored = $this->form->getState()['file'] ?? null;
            $stored = is_array($stored) ? reset($stored) : $stored;

            if (! $stored) {
                Notification::make()->title('No file')->body('Choose a file to upload.')->danger()->send();

                return null;
            }

            $this->uploadedAbsolutePath = Storage::disk('local')->path($stored);
        }

        return Excel::toArray(new HeadingRowsImport, $this->uploadedAbsolutePath)[0] ?? [];
    }
}
