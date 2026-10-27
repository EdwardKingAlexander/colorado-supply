<?php

namespace App\Filament\Pages;

use App\Models\DibbsFile;
use App\Services\Dibbs\DibbsFileImporter;
use App\Services\Dibbs\DibbsRfqMatcher;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use UnitEnum;

/**
 * Manual DIBBS import: the fallback when the automated download is blocked or a
 * day was missed (ai/modules/dibbs-rfq-tracking, Phase 2.5). Uses the same
 * importer as the scheduled job, so re-uploading a file is a no-op.
 */
class DibbsUpload extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static UnitEnum|string|null $navigationGroup = 'Automation';

    protected static ?string $navigationLabel = 'Upload DIBBS File';

    protected static ?string $title = 'Upload DIBBS RFQ Files';

    protected static ?string $slug = 'dibbs-upload';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.dibbs-upload';

    public ?array $formData = ['files' => []];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('DIBBS daily files')
                    ->description('In DIBBS go to Solicitations → RFQs → Recent, download the day\'s Index file (inYYMMDD.txt) and Batch Quote file (bqYYMMDD.zip), and upload them here. Keep DLA\'s file names: they carry the date and type.')
                    ->schema([
                        FileUpload::make('files')
                            ->label('Files')
                            ->multiple()
                            ->maxFiles(10)
                            ->maxSize(20 * 1024)
                            ->acceptedFileTypes(['text/plain', 'application/zip', 'application/x-zip-compressed', 'application/octet-stream'])
                            ->disk('local')
                            ->directory('dibbs-uploads')
                            ->visibility('private')
                            ->preserveFilenames()
                            ->required(),
                    ]),
            ])
            ->statePath('formData');
    }

    public function import(): void
    {
        $paths = (array) ($this->form->getState()['files'] ?? []);
        $importer = app(DibbsFileImporter::class);
        $imported = [];
        $problems = [];

        foreach ($paths as $path) {
            $name = basename($path);

            try {
                $file = $importer->import($importer->register(Storage::disk('local')->get($path), $name, 'upload'));

                $file->isImported()
                    ? $imported[] = "{$file->name} ({$file->row_count} rows".($file->error_count ? ", {$file->error_count} row problems" : '').')'
                    : $problems[] = "{$file->name}: {$file->error}";
            } catch (InvalidArgumentException $e) {
                $problems[] = $e->getMessage();
            } finally {
                Storage::disk('local')->delete($path); // the importer keeps its own copy
            }
        }

        if ($imported !== []) {
            app(DibbsRfqMatcher::class)->rebuild();

            Notification::make()->title('Imported')->body(implode("\n", $imported))->success()->send();
        }

        if ($problems !== []) {
            Notification::make()->title('Some files were not imported')->body(implode("\n", $problems))->danger()->persistent()->send();
        }

        $this->form->fill();
    }

    /**
     * @return Collection<int, DibbsFile>
     */
    public function getRecentFiles(): Collection
    {
        return DibbsFile::latest()->limit(15)->get();
    }
}
