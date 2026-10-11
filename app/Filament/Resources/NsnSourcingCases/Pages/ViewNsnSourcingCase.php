<?php

namespace App\Filament\Resources\NsnSourcingCases\Pages;

use App\Filament\Resources\NsnSourcingCases\NsnSourcingCaseResource;
use App\Models\NsnCageVerification;
use App\Models\NsnSourcingCase;
use App\Models\NsnSourcingFile;
use App\Models\NsnSourcingInquiry;
use App\Models\NsnSourcingResponse;
use App\Models\Supplier;
use App\Models\VendorContact;
use App\Services\NsnSourcing\InquiryTemplate;
use App\Services\NsnSourcing\NsnSourcingService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @property NsnSourcingCase $record
 */
class ViewNsnSourcingCase extends ViewRecord
{
    protected static string $resource = NsnSourcingCaseResource::class;

    public function getTitle(): string
    {
        return 'NSN '.$this->record->formattedNsn();
    }

    public function getSubheading(): ?string
    {
        return $this->record->part_number.' · CAGE '.$this->record->cage_code.($this->record->item_name ? ' · '.$this->record->item_name : '');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->recordResponseAction(),
            $this->draftInquiryAction(),
            $this->markContactedAction(),
            ActionGroup::make([
                $this->verifyCageAction(),
                $this->addContactAction(),
                $this->followUpAction(),
                $this->linkSupplierAction(),
                Action::make('cageSearch')->label('Open CAGE Search')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url('https://cage.dla.mil/Search', shouldOpenInNewTab: true),
                $this->closeAction(),
            ])->label('More')->icon('heroicon-o-ellipsis-vertical')->button()->color('gray'),
        ];
    }

    /**
     * Streams one evidence file belonging to this case as a download.
     */
    public function downloadEvidence(int $fileId): StreamedResponse
    {
        Gate::authorize('view', $this->record);
        $file = NsnSourcingFile::with('response')->find($fileId) ?? abort(404);
        abort_unless((int) $file->response?->nsn_sourcing_case_id === (int) $this->record->id, 404);

        return Storage::disk(NsnSourcingService::FILE_DISK)->download($file->path, $file->original_name, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function verifyCageAction(): Action
    {
        return $this->caseAction('verifyCage', 'Record CAGE verification', 'heroicon-o-shield-check')
            ->modalDescription('Record what CAGE Search shows for CAGE '.$this->record->cage_code.'. This does not change the PUB LOG snapshot.')
            ->schema([
                Select::make('outcome')->options(NsnCageVerification::OUTCOMES)->required()->native(false),
                TextInput::make('verified_name')->label('Company name in CAGE Search')->maxLength(255)->requiredIf('outcome', 'verified'),
                Textarea::make('verified_address')->label('Address')->rows(2)->maxLength(2000),
                TextInput::make('verified_status')->label('CAGE status shown')->maxLength(255),
                TextInput::make('source_url')->label('CAGE Search URL')->url()->required()->maxLength(2048)->default('https://cage.dla.mil/Search'),
                DatePicker::make('verified_on')->label('Checked on')->required()->default(today())->maxDate(today()),
                Textarea::make('notes')->rows(2)->maxLength(5000),
            ])
            ->action(fn (array $data, Action $action) => $this->run($action, fn (NsnSourcingService $s) => $s->recordVerification($this->record, $data), 'Verification recorded'));
    }

    private function addContactAction(): Action
    {
        return $this->caseAction('addContact', 'Add sales contact', 'heroicon-o-user-plus')
            ->modalDescription('Record where you found this contact. Do not guess email addresses; nothing here is copied to shared Supplier or Vendor records.')
            ->schema([
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    TextInput::make('company_name')->maxLength(255),
                    TextInput::make('website')->url()->maxLength(2048),
                    TextInput::make('contact_name')->maxLength(255),
                    TextInput::make('stated_role')->label('Role as stated by the source')->maxLength(255),
                    TextInput::make('email')->email()->maxLength(255),
                    TextInput::make('phone')->tel()->maxLength(64),
                    TextInput::make('source_url')->label('Found at (URL)')->url()->required()->maxLength(2048),
                    DatePicker::make('source_on')->label('Found on')->required()->default(today())->maxDate(today()),
                ]),
                Textarea::make('notes')->rows(2)->maxLength(5000),
                Select::make('vendor_contact_id')->label('Link to an existing vendor contact (optional)')->searchable()
                    ->getSearchResultsUsing(fn (string $search) => VendorContact::query()->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->limit(20)->pluck('name', 'id'))
                    ->getOptionLabelUsing(fn ($value) => VendorContact::find($value)?->name),
                Select::make('supplier_id')->label('Link to an existing supplier (optional)')->searchable()
                    ->getSearchResultsUsing(fn (string $search) => Supplier::query()->where('name', 'like', "%{$search}%")->orWhere('cage_code', $search)->limit(20)->pluck('name', 'id'))
                    ->getOptionLabelUsing(fn ($value) => Supplier::find($value)?->name),
            ])
            ->action(fn (array $data, Action $action) => $this->run($action, fn (NsnSourcingService $s) => $s->addContact($this->record, $data), 'Contact added'));
    }

    private function draftInquiryAction(): Action
    {
        return $this->caseAction('draftInquiry', 'Draft inquiry', 'heroicon-o-pencil-square')
            ->color('gray')
            ->modalDescription('Edit the message, save it, then send it yourself (copy it or use "Open in email app" on the case). The app never sends it.')
            ->modalWidth('3xl')
            ->schema([
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    Select::make('nsn_sales_contact_id')->label('Contact')->native(false)
                        ->options(fn () => $this->record->contacts()->get()->mapWithKeys(fn ($c) => [$c->id => $c->label()]))
                        ->default(fn () => $this->record->contacts()->value('id')),
                    Select::make('channel')->options(NsnSourcingInquiry::CHANNELS)->default('email')->required()->native(false),
                ]),
                TextInput::make('subject')->maxLength(255)->default(fn () => InquiryTemplate::subject($this->record)),
                Textarea::make('body')->label('Message')->rows(16)->required()->maxLength(20000)
                    ->default(fn () => InquiryTemplate::body($this->record, $this->record->contacts()->first())),
            ])
            ->action(fn (array $data, Action $action) => $this->run($action, fn (NsnSourcingService $s) => $s->draftInquiry($this->record, $data), 'Draft saved - not sent'));
    }

    private function markContactedAction(): Action
    {
        return $this->caseAction('markContacted', 'Mark contacted', 'heroicon-o-paper-airplane')
            ->color('gray')
            ->visible(fn () => $this->record->isOpen() && $this->record->inquiries()->whereNull('contacted_at')->exists())
            ->modalDescription('Record that you actually sent the message or made the call.')
            ->schema([
                Select::make('inquiry_id')->label('Inquiry')->required()->native(false)
                    ->options(fn () => $this->record->inquiries()->whereNull('contacted_at')->get()->mapWithKeys(fn (NsnSourcingInquiry $i) => [$i->id => '#'.$i->id.' '.(NsnSourcingInquiry::CHANNELS[$i->channel] ?? $i->channel).($i->contact ? ' to '.$i->contact->label() : '').' (drafted '.$i->created_at->format('M j').')']))
                    ->default(fn () => $this->record->inquiries()->whereNull('contacted_at')->value('id')),
                DatePicker::make('contacted_on')->label('Contacted on')->required()->default(today())->maxDate(today()),
                DatePicker::make('follow_up_on')->label('Follow up on')->default(today()->addWeek()),
            ])
            ->action(function (array $data, Action $action) {
                $inquiry = $this->record->inquiries()->whereKey($data['inquiry_id'])->first() ?? abort(404);
                $this->run($action, fn (NsnSourcingService $s) => $s->markContacted($inquiry, $data), 'Marked contacted');
            });
    }

    private function recordResponseAction(): Action
    {
        return $this->caseAction('recordResponse', 'Record response', 'heroicon-o-inbox-arrow-down')
            ->modalDescription('Record what the company actually said. Responses cannot be edited later; record a new one that supersedes it instead.')
            ->modalWidth('4xl')
            ->schema([
                Grid::make(['default' => 1, 'md' => 3])->schema([
                    DatePicker::make('received_on')->label('Received on')->required()->default(today())->maxDate(today()),
                    Select::make('channel')->options(NsnSourcingResponse::CHANNELS)->default('email')->required()->native(false),
                    TextInput::make('responder')->label('From')->maxLength(255),
                ]),
                Textarea::make('body')->label('Reply text or call notes')->rows(6)->required()->maxLength(50000),
                Grid::make(['default' => 1, 'md' => 3])->schema([
                    Select::make('production_state')->label('Production, as stated')->options(NsnSourcingCase::REPORTED_STATES)->required()->native(false)
                        ->helperText('Choose "Not stated" unless the reply says so.'),
                    Select::make('sells_direct')->label('Sells direct')->options(NsnSourcingResponse::SELLS_DIRECT)->default('unknown')->required()->native(false),
                    TextInput::make('lead_time_days')->label('Lead time (days)')->numeric()->minValue(0)->maxValue(3650),
                    TextInput::make('minimum_order')->label('Minimum order')->maxLength(255),
                    TextInput::make('replacement_nsn')->label('Replacement NSN')->maxLength(16),
                    TextInput::make('replacement_part_number')->label('Replacement part number')->maxLength(255),
                ]),
                Repeater::make('distributors')->label('Distributors named in the reply')->defaultItems(0)->collapsible()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            TextInput::make('name')->required()->maxLength(255),
                            TextInput::make('cage_code')->label('CAGE')->maxLength(5),
                            TextInput::make('contact')->maxLength(255),
                            TextInput::make('authorization')->label('Authorization evidence')->maxLength(2000),
                        ]),
                    ]),
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    TextInput::make('source_url')->label('Source URL (optional)')->url()->maxLength(2048),
                    Select::make('nsn_sourcing_inquiry_id')->label('In reply to (optional)')->native(false)
                        ->options(fn () => $this->record->inquiries()->get()->mapWithKeys(fn (NsnSourcingInquiry $i) => [$i->id => 'Inquiry #'.$i->id.' ('.$i->created_at->format('M j').')'])),
                    Select::make('supersedes_response_id')->label('Supersedes an earlier response (optional)')->native(false)
                        ->options(fn () => $this->record->responses()->get()->mapWithKeys(fn (NsnSourcingResponse $r) => [$r->id => '#'.$r->id.' '.$r->received_on->format('M j, Y').' - '.(NsnSourcingCase::REPORTED_STATES[$r->production_state] ?? $r->production_state)])),
                ]),
                FileUpload::make('files')->label('Evidence files (optional)')->multiple()->maxFiles(10)->storeFiles(false)
                    ->maxSize(NsnSourcingService::MAX_FILE_KB)->helperText('PDF, PNG, JPG, WebP, .eml or .msg; up to 10 MB each. Stored privately.'),
            ])
            ->action(function (array $data, Action $action) {
                $files = array_values(array_filter((array) ($data['files'] ?? [])));
                unset($data['files']);
                $this->run($action, fn (NsnSourcingService $s) => $s->recordResponse($this->record, $data, $files), 'Response recorded');
            });
    }

    private function followUpAction(): Action
    {
        return $this->caseAction('followUp', 'Set follow-up date', 'heroicon-o-calendar')
            ->schema([DatePicker::make('follow_up_on')->label('Follow up on')->default(fn () => $this->record->follow_up_on)])
            ->action(fn (array $data, Action $action) => $this->run($action, fn (NsnSourcingService $s) => $s->setFollowUp($this->record, $data['follow_up_on'] ?? null), 'Follow-up updated'));
    }

    private function linkSupplierAction(): Action
    {
        return Action::make('linkSupplier')->label('Add supplier to curated part')->icon('heroicon-o-link')
            ->visible(fn () => $this->record->responses()->exists())
            ->modalDescription('Adds this company to the curated part for this NSN. Only the name, CAGE and website are saved to the shared supplier; contacts stay on this case.')
            ->schema([
                Select::make('supplier_id')->label('Existing supplier')->searchable()->live()
                    ->getSearchResultsUsing(fn (string $search) => Supplier::query()->where('name', 'like', "%{$search}%")->orWhere('cage_code', $search)->limit(20)->pluck('name', 'id'))
                    ->getOptionLabelUsing(fn ($value) => Supplier::find($value)?->name)
                    ->default(fn () => Supplier::where('cage_code', $this->record->cage_code)->value('id'))
                    ->helperText('Or leave empty and create a new supplier below.'),
                Grid::make(['default' => 1, 'md' => 3])->visible(fn ($get) => blank($get('supplier_id')))->schema([
                    TextInput::make('name')->label('New supplier name')->requiredWithout('supplier_id')->maxLength(255)
                        ->default(fn () => $this->record->verifications()->where('outcome', 'verified')->value('verified_name')),
                    TextInput::make('cage_code')->label('CAGE')->maxLength(5)->default(fn () => $this->record->cage_code),
                    TextInput::make('website')->url()->maxLength(2048)->default(fn () => $this->record->contacts()->whereNotNull('website')->value('website')),
                ]),
                TextInput::make('supplier_part_number')->label('Supplier part number')->maxLength(255)->default(fn () => $this->record->part_number),
            ])
            ->action(fn (array $data, Action $action) => $this->run($action, fn (NsnSourcingService $s) => $s->linkSupplier($this->record, $data), 'Supplier added to the curated part'));
    }

    private function closeAction(): Action
    {
        return $this->caseAction('close', 'Close case', 'heroicon-o-archive-box-x-mark')->color('danger')
            ->requiresConfirmation()
            ->schema([TextInput::make('close_reason')->label('Reason')->required()->maxLength(255)])
            ->action(fn (array $data, Action $action) => $this->run($action, fn (NsnSourcingService $s) => $s->close($this->record, $data['close_reason']), 'Case closed'));
    }

    private function caseAction(string $name, string $label, string $icon): Action
    {
        return Action::make($name)->label($label)->icon($icon)
            ->visible(fn () => $this->record->isOpen())
            ->authorize(fn () => Gate::allows('update', $this->record));
    }

    /**
     * Runs a service call, turning rule failures into a notification that
     * keeps the modal open, and refreshes the case afterwards.
     */
    private function run(Action $action, callable $callback, string $success): void
    {
        try {
            $callback(app(NsnSourcingService::class));
        } catch (ValidationException $e) {
            Notification::make()->title('Not saved')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
            $action->halt();
        } catch (HttpException $e) {
            Notification::make()->title('Not saved')->body($e->getMessage() ?: 'You cannot change this case.')->danger()->send();
            $action->halt();
        }
        $this->record->refresh();
        Notification::make()->title($success)->success()->send();
    }
}
