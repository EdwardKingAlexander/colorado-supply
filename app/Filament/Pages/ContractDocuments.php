<?php

namespace App\Filament\Pages;

use App\Jobs\ParseDocumentJob;
use App\Models\ContractDocument;
use App\Models\SamOpportunity;
use App\Services\CuiDetectionService;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

class ContractDocuments extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.contract-documents';

    protected static UnitEnum|string|null $navigationGroup = 'Automation';

    protected static ?string $title = 'Contract Documents';

    protected static ?string $navigationLabel = 'Contract Documents';

    protected static ?int $navigationSort = 4;

    public ?array $uploadData = [];

    public function getSubheading(): ?string
    {
        return 'Upload, parse, and analyze government contract documents';
    }

    public function mount(): void
    {
        $this->uploadData = [
            'files' => [],
            'document_type' => null,
            'sam_opportunity_id' => null,
            'notes' => null,
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Upload Documents')
                    ->description('Upload RFPs, RFQs, IFBs, amendments, and attachments')
                    ->schema([
                        FileUpload::make('files')
                            ->label('Select Files')
                            ->multiple()
                            ->maxFiles(10)
                            ->maxSize(51200) // 50MB
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/msword',
                                'application/vnd.ms-excel',
                            ])
                            ->helperText('PDF, DOCX, XLSX (max 50MB each, up to 10 files)')
                            ->columnSpanFull()
                            ->required(),

                        Select::make('document_type')
                            ->label('Document Type')
                            ->options(ContractDocument::getTypeOptions())
                            ->placeholder('Select type...')
                            ->helperText('Applies to all uploaded files'),

                        Select::make('sam_opportunity_id')
                            ->label('Link to SAM Opportunity')
                            ->options(function () {
                                return SamOpportunity::query()
                                    ->orderByDesc('created_at')
                                    ->limit(100)
                                    ->get()
                                    ->mapWithKeys(fn ($opp) => [
                                        $opp->id => Str::limit($opp->title, 60) . ' (' . $opp->notice_id . ')',
                                    ]);
                            })
                            ->searchable()
                            ->placeholder('Optional - link to opportunity')
                            ->helperText('Associate these documents with a SAM.gov opportunity'),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->placeholder('Optional notes about these documents...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('uploadData');
    }

    public function upload(): void
    {
        $data = $this->uploadData;

        if (empty($data['files'])) {
            Notification::make()
                ->title('No files selected')
                ->body('Please select at least one file to upload.')
                ->warning()
                ->send();

            return;
        }

        $uploaded = 0;
        $failed = 0;

        foreach ($data['files'] as $filePath) {
            try {
                // Get file from Livewire's temp storage
                $tempPath = storage_path('app/livewire-tmp/' . $filePath);

                if (! file_exists($tempPath)) {
                    // Try alternate path
                    $tempPath = Storage::disk('local')->path('livewire-tmp/' . $filePath);
                }

                if (! file_exists($tempPath)) {
                    $failed++;

                    continue;
                }

                // Generate storage path
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                $storagePath = date('Y/m/d') . '/' . Str::uuid() . '.' . $extension;

                // Get file info before moving
                $originalName = $filePath; // Livewire stores with original name
                $fileSize = filesize($tempPath);
                $checksum = hash_file('sha256', $tempPath);
                $mimeType = mime_content_type($tempPath);

                // Move to permanent storage
                Storage::disk(ContractDocument::DISK)->put(
                    $storagePath,
                    file_get_contents($tempPath)
                );

                // Create database record
                $document = ContractDocument::create([
                    'original_filename' => $originalName,
                    'mime_type' => $mimeType,
                    'storage_disk' => ContractDocument::DISK,
                    'storage_path' => $storagePath,
                    'checksum' => $checksum,
                    'file_size_bytes' => $fileSize,
                    'document_type' => $data['document_type'],
                    'sam_opportunity_id' => $data['sam_opportunity_id'],
                    'status' => ContractDocument::STATUS_PENDING,
                    'uploaded_by' => Auth::guard('web')->id(),
                    'uploaded_at' => now(),
                ]);

                // Log the upload action
                $document->logAction('uploaded', [
                    'filename' => $originalName,
                    'size' => $fileSize,
                    'mime_type' => $mimeType,
                ]);

                // Run CUI detection on filename
                $cuiService = new CuiDetectionService();
                $cuiCategories = $cuiService->quickScan($document);
                if (! empty($cuiCategories)) {
                    $cuiService->updateDocumentCuiStatus($document, $cuiCategories);
                }

                // Dispatch parsing job
                ParseDocumentJob::dispatch($document);

                // Clean up temp file
                @unlink($tempPath);

                $uploaded++;
            } catch (\Exception $e) {
                $failed++;
                logger()->error('Contract document upload failed', [
                    'error' => $e->getMessage(),
                    'file' => $filePath,
                ]);
            }
        }

        // Reset form
        $this->uploadData = [
            'files' => [],
            'document_type' => $data['document_type'], // Keep type for batch uploads
            'sam_opportunity_id' => $data['sam_opportunity_id'], // Keep opportunity
            'notes' => null,
        ];

        if ($uploaded > 0) {
            Notification::make()
                ->title('Upload Complete')
                ->body("Successfully uploaded {$uploaded} document(s). Parsing queued." . ($failed > 0 ? " {$failed} failed." : ''))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Upload Failed')
                ->body('No documents were uploaded. Please try again.')
                ->danger()
                ->send();
        }
    }

    /**
     * Manually trigger parsing for a document.
     */
    public function parseDocument(int $documentId): void
    {
        $document = ContractDocument::find($documentId);

        if (! $document) {
            Notification::make()
                ->title('Document Not Found')
                ->danger()
                ->send();

            return;
        }

        // Reset status and dispatch job
        $document->update(['status' => ContractDocument::STATUS_PENDING]);
        ParseDocumentJob::dispatch($document);

        Notification::make()
            ->title('Parsing Queued')
            ->body("Document '{$document->original_filename}' has been queued for parsing.")
            ->success()
            ->send();
    }

    public function getDocuments()
    {
        return ContractDocument::query()
            ->with(['opportunity', 'uploader'])
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function getDocumentStats(): array
    {
        return [
            'total' => ContractDocument::count(),
            'pending' => ContractDocument::where('status', ContractDocument::STATUS_PENDING)->count(),
            'processing' => ContractDocument::where('status', ContractDocument::STATUS_PROCESSING)->count(),
            'parsed' => ContractDocument::where('status', ContractDocument::STATUS_PARSED)->count(),
            'failed' => ContractDocument::where('status', ContractDocument::STATUS_FAILED)->count(),
            'with_cui' => ContractDocument::where('cui_detected', true)->count(),
        ];
    }
}
