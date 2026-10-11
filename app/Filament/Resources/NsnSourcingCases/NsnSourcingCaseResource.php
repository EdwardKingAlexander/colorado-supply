<?php

namespace App\Filament\Resources\NsnSourcingCases;

use App\Filament\Resources\NsnSourcingCases\Pages\ListNsnSourcingCases;
use App\Filament\Resources\NsnSourcingCases\Pages\ViewNsnSourcingCase;
use App\Models\NsnCageVerification;
use App\Models\NsnSourcingCase;
use App\Models\NsnSourcingInquiry;
use App\Models\NsnSourcingResponse;
use BackedEnum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Private NSN sourcing cases (ai/modules/nsn-sourcing-workflow, phase 4).
 * Cases start from PUB LOG Lookup; there is no blank "create" form.
 */
class NsnSourcingCaseResource extends Resource
{
    protected static ?string $model = NsnSourcingCase::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static UnitEnum|string|null $navigationGroup = 'NSN Procurement';

    protected static ?string $navigationLabel = 'Sourcing Cases';

    protected static ?string $modelLabel = 'sourcing case';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'nsn-sourcing-cases';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('latestResponse'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('nsn')->label('NSN')->formatStateUsing(fn (NsnSourcingCase $record) => $record->formattedNsn())->fontFamily('mono')->searchable()
                    ->description(fn (NsnSourcingCase $record) => $record->part_number.' · CAGE '.$record->cage_code.($record->item_name ? ' · '.$record->item_name : ''))->wrap(),
                TextColumn::make('item_name')->label('Item')->wrap()->visibleFrom('2xl'),
                TextColumn::make('status')->badge()->visibleFrom('md')->formatStateUsing(fn (string $state) => NsnSourcingCase::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => NsnSourcingCase::STATUS_COLORS[$state] ?? 'gray'),
                TextColumn::make('production_state')->label('Production')->badge()
                    ->formatStateUsing(fn (string $state) => NsnSourcingCase::PRODUCTION_STATES[$state] ?? $state)
                    ->color(fn (string $state) => NsnSourcingCase::PRODUCTION_COLORS[$state] ?? 'gray'),
                TextColumn::make('latestResponse.received_on')->label('Last answer')->date('M j, Y')->placeholder('No answer')->visibleFrom('2xl'),
                TextColumn::make('follow_up_on')->label('Follow up')->date('M j, Y')->placeholder('—')->sortable()->visibleFrom('lg')
                    ->color(fn (NsnSourcingCase $record) => $record->follow_up_on?->isPast() || $record->follow_up_on?->isToday() ? 'danger' : null),
                TextColumn::make('part_number')->searchable()->hidden(),
                TextColumn::make('cage_code')->searchable()->hidden(),
            ])
            ->filters([
                SelectFilter::make('status')->multiple()->options(NsnSourcingCase::STATUSES)->default(['open', 'awaiting_response', 'answered']),
                SelectFilter::make('production_state')->label('Production')->options(NsnSourcingCase::PRODUCTION_STATES),
                Filter::make('follow_up_due')->label('Follow-up due')
                    ->query(fn (Builder $query) => $query->whereDate('follow_up_on', '<=', today())->where('status', '!=', 'closed')),
            ])
            ->recordUrl(fn (NsnSourcingCase $record) => static::getUrl('view', ['record' => $record]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Case')
                ->columns(['default' => 1, 'md' => 3])
                ->schema([
                    TextEntry::make('status')->badge()->formatStateUsing(fn (string $state) => NsnSourcingCase::STATUSES[$state] ?? $state)
                        ->color(fn (string $state) => NsnSourcingCase::STATUS_COLORS[$state] ?? 'gray'),
                    TextEntry::make('production_state')->label('Production')->badge()
                        ->formatStateUsing(fn (string $state) => NsnSourcingCase::PRODUCTION_STATES[$state] ?? $state)
                        ->color(fn (string $state) => NsnSourcingCase::PRODUCTION_COLORS[$state] ?? 'gray')
                        ->helperText(fn (NsnSourcingCase $record) => match ($record->production_state) {
                            'unknown' => 'No recorded response states this yet.',
                            'conflicting' => 'Recorded responses disagree. Record a new response that supersedes the wrong one.',
                            default => 'From the latest recorded response(s).',
                        }),
                    TextEntry::make('follow_up_on')->label('Follow up')->date('M j, Y')->placeholder('Not set'),
                    TextEntry::make('close_reason')->label('Closed')->visible(fn (NsnSourcingCase $record) => $record->status === 'closed')
                        ->state(fn (NsnSourcingCase $record) => $record->close_reason.' ('.$record->closed_at?->format('M j, Y').')'),
                ]),
            Section::make('PUB LOG snapshot - not verified')
                ->description('Catalog values from the release this case was opened from. They do not confirm current production or authorization.')
                ->columns(['default' => 1, 'md' => 3])
                ->schema([
                    TextEntry::make('nsn')->label('NSN')->state(fn (NsnSourcingCase $record) => $record->formattedNsn())->fontFamily('mono')->copyable(),
                    TextEntry::make('part_number')->label('Part number')->fontFamily('mono')->copyable(),
                    TextEntry::make('cage_code')->label('CAGE')->fontFamily('mono')->copyable()
                        ->url('https://cage.dla.mil/Search', shouldOpenInNewTab: true)->helperText('Opens CAGE Search'),
                    TextEntry::make('item_name')->label('Item')->placeholder('—'),
                    TextEntry::make('release_date')->label('PUB LOG release')->date('M j, Y'),
                    TextEntry::make('created_at')->label('Opened')->dateTime('M j, Y')
                        ->helperText(fn (NsnSourcingCase $record) => 'by '.($record->openedBy?->name ?? 'unknown')),
                ]),
            Section::make('CAGE verification')
                ->description('What CAGE Search showed. The newest entry is current.')
                ->schema([
                    TextEntry::make('no_verification')->hiddenLabel()->state('Not verified yet. Use "Record CAGE verification".')
                        ->visible(fn (NsnSourcingCase $record) => $record->verifications->isEmpty()),
                    RepeatableEntry::make('verifications')->hiddenLabel()->columns(['default' => 1, 'md' => 3])
                        ->visible(fn (NsnSourcingCase $record) => $record->verifications->isNotEmpty())
                        ->schema([
                            TextEntry::make('outcome')->badge()->formatStateUsing(fn (string $state) => NsnCageVerification::OUTCOMES[$state] ?? $state)
                                ->color(fn (string $state) => NsnCageVerification::OUTCOME_COLORS[$state] ?? 'gray'),
                            TextEntry::make('verified_on')->label('Checked')->date('M j, Y'),
                            TextEntry::make('verified_name')->label('Name')->placeholder('—'),
                            TextEntry::make('verified_address')->label('Address')->placeholder('—'),
                            TextEntry::make('verified_status')->label('Status')->placeholder('—'),
                            TextEntry::make('source_url')->label('Source')->url(fn (?string $state) => $state, shouldOpenInNewTab: true)->limit(40),
                            TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                        ]),
                ]),
            Section::make('Sales contacts')
                ->schema([
                    TextEntry::make('no_contacts')->hiddenLabel()->state('No contacts yet. Use "Add sales contact".')
                        ->visible(fn (NsnSourcingCase $record) => $record->contacts->isEmpty()),
                    RepeatableEntry::make('contacts')->hiddenLabel()->columns(['default' => 1, 'md' => 3])
                        ->visible(fn (NsnSourcingCase $record) => $record->contacts->isNotEmpty())
                        ->schema([
                            TextEntry::make('company_name')->label('Company')->placeholder('—'),
                            TextEntry::make('contact_name')->label('Contact')->placeholder('—')->helperText(fn ($record) => $record->stated_role),
                            TextEntry::make('email')->placeholder('—')->copyable(),
                            TextEntry::make('phone')->placeholder('—')->copyable(),
                            TextEntry::make('website')->placeholder('—')->url(fn (?string $state) => $state, shouldOpenInNewTab: true)->limit(40),
                            TextEntry::make('source_url')->label('Found at')->url(fn (?string $state) => $state, shouldOpenInNewTab: true)->limit(40)
                                ->helperText(fn ($record) => 'on '.$record->source_on->format('M j, Y')),
                        ]),
                ]),
            Section::make('Inquiries')
                ->schema([
                    TextEntry::make('no_inquiries')->hiddenLabel()->state('No inquiries yet. Use "Draft inquiry".')
                        ->visible(fn (NsnSourcingCase $record) => $record->inquiries->isEmpty()),
                    RepeatableEntry::make('inquiries')->hiddenLabel()->columns(['default' => 1, 'md' => 3])
                        ->visible(fn (NsnSourcingCase $record) => $record->inquiries->isNotEmpty())
                        ->schema([
                            TextEntry::make('contacted_at')->label('Status')->badge()
                                ->state(fn (NsnSourcingInquiry $record) => $record->isContacted() ? 'Contacted '.$record->contacted_at->format('M j, Y') : 'Draft - not sent')
                                ->color(fn (NsnSourcingInquiry $record) => $record->isContacted() ? 'success' : 'warning'),
                            TextEntry::make('channel')->formatStateUsing(fn (string $state) => NsnSourcingInquiry::CHANNELS[$state] ?? $state)
                                ->helperText(fn (NsnSourcingInquiry $record) => $record->contact?->label()),
                            TextEntry::make('mailto')->label('Hand off')
                                ->state(fn (NsnSourcingInquiry $record) => $record->contact?->email ? 'Open in email app' : 'Copy the text below')
                                ->url(fn (NsnSourcingInquiry $record) => static::mailto($record))
                                ->color(fn (NsnSourcingInquiry $record) => $record->contact?->email ? 'primary' : null),
                            TextEntry::make('subject')->placeholder('—')->columnSpanFull()->copyable(),
                            TextEntry::make('body')->label('Message')->columnSpanFull()->copyable()
                                ->extraAttributes(['style' => 'white-space: pre-line']),
                        ]),
                ]),
            Section::make('Responses')
                ->description('Recorded answers are never edited. A newer response can supersede an older one; conflicts stay visible.')
                ->schema([
                    TextEntry::make('no_responses')->hiddenLabel()->state('No responses recorded. Production stays Unknown until one is.')
                        ->visible(fn (NsnSourcingCase $record) => $record->responses->isEmpty()),
                    RepeatableEntry::make('responses')->hiddenLabel()->columns(['default' => 1, 'md' => 3])
                        ->visible(fn (NsnSourcingCase $record) => $record->responses->isNotEmpty())
                        ->schema([
                            TextEntry::make('production_state')->label('Production')->badge()
                                ->formatStateUsing(fn (string $state) => NsnSourcingCase::REPORTED_STATES[$state] ?? $state)
                                ->color(fn (string $state) => NsnSourcingCase::PRODUCTION_COLORS[$state] ?? 'gray')
                                ->helperText(fn (NsnSourcingResponse $record) => static::supersededNote($record)),
                            TextEntry::make('received_on')->label('Received')->date('M j, Y')
                                ->helperText(fn (NsnSourcingResponse $record) => (NsnSourcingResponse::CHANNELS[$record->channel] ?? $record->channel).($record->responder ? ' from '.$record->responder : '')),
                            TextEntry::make('sells_direct')->label('Sells direct')->formatStateUsing(fn (string $state) => NsnSourcingResponse::SELLS_DIRECT[$state] ?? $state),
                            TextEntry::make('minimum_order')->label('Minimum order')->placeholder('—'),
                            TextEntry::make('lead_time_days')->label('Lead time')->placeholder('—')->suffix(' days'),
                            TextEntry::make('replacement')->label('Replacement')->placeholder('—')
                                ->state(fn (NsnSourcingResponse $record) => collect([$record->replacement_nsn ? 'NSN '.$record->replacement_nsn : null, $record->replacement_part_number ? 'P/N '.$record->replacement_part_number : null])->filter()->implode(' · ') ?: null),
                            TextEntry::make('distributors')->label('Distributors')->placeholder('None stated')->columnSpanFull()
                                ->state(fn (NsnSourcingResponse $record) => collect($record->distributors ?? [])->map(fn (array $d) => $d['name'].(! empty($d['cage_code']) ? " (CAGE {$d['cage_code']})" : '').(! empty($d['authorization']) ? ' - '.$d['authorization'] : ''))->all() ?: null)
                                ->listWithLineBreaks()->bulleted(),
                            TextEntry::make('body')->label('Reply / notes')->columnSpanFull()->extraAttributes(['style' => 'white-space: pre-line']),
                            TextEntry::make('source_url')->label('Source')->placeholder('—')->url(fn (?string $state) => $state, shouldOpenInNewTab: true)->limit(50),
                            ViewEntry::make('files')->label('Evidence files')->view('filament.nsn-sourcing.response-files')->columnSpan(['default' => 1, 'md' => 2]),
                        ]),
                ]),
        ]);
    }

    public static function mailto(NsnSourcingInquiry $inquiry): ?string
    {
        $email = $inquiry->contact?->email;
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return 'mailto:'.rawurlencode($email).'?subject='.rawurlencode((string) $inquiry->subject).'&body='.rawurlencode($inquiry->body);
    }

    public static function supersededNote(NsnSourcingResponse $response): ?string
    {
        $by = NsnSourcingResponse::where('supersedes_response_id', $response->id)->value('id');

        return match (true) {
            $by !== null => "Superseded by response #{$by}",
            $response->supersedes_response_id !== null => "Response #{$response->id}, supersedes #{$response->supersedes_response_id}",
            default => "Response #{$response->id}",
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNsnSourcingCases::route('/'),
            'view' => ViewNsnSourcingCase::route('/{record}'),
        ];
    }
}
