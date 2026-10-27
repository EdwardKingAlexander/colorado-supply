<?php

namespace App\Filament\Resources;

use App\Filament\Pages\DibbsQuoteWorksheet;
use App\Filament\Resources\DibbsRfqResource\Pages;
use App\Jobs\RebuildDibbsMatchesJob;
use App\Models\Admin;
use App\Models\DibbsApprovedSource;
use App\Models\DibbsFile;
use App\Models\DibbsRfq;
use App\Models\DibbsRfqMatch;
use App\Models\DibbsRfqTracking;
use App\Models\Manufacturer;
use App\Models\MilSpecPart;
use App\Models\Supplier;
use App\Services\Catalog\MilSpecPartCatalogImporter;
use App\Services\Dibbs\DibbsRfqMatcher;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

/**
 * DIBBS RFQs (ai/modules/dibbs-rfq-tracking, Phase 3): triage the day's DLA
 * RFQs and track the ones being pursued. Read-only data from DLA's daily files;
 * the company's status, owner and notes live on DibbsRfqTracking.
 */
class DibbsRfqResource extends Resource
{
    protected static ?string $model = DibbsRfq::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Automation';

    protected static ?string $navigationLabel = 'DIBBS RFQs';

    protected static ?string $modelLabel = 'DIBBS RFQ';

    protected static ?string $pluralModelLabel = 'DIBBS RFQs';

    protected static ?string $slug = 'dibbs-rfqs';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'solicitation_number';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['tracking.assignee', 'matches'])
                ->withMax('matches', 'score'))
            ->columns([
                // Phones: one condensed column (pattern from CRM Opportunities, 2026-10-04).
                TextColumn::make('mobile_summary')
                    ->label('RFQ')
                    ->state(fn (DibbsRfq $record) => $record->nomenclature ?: $record->formattedNsn())
                    ->description(fn (DibbsRfq $record) => collect([
                        $record->bestMatchReason() ? '★ '.DibbsRfqMatch::LABELS[$record->bestMatchReason()] : null,
                        $record->formattedNsn(),
                        $record->quantity !== null ? $record->quantity.' '.$record->unit_of_issue : null,
                        static::closesIn($record),
                        DibbsRfqTracking::STATUSES[$record->trackingStatus()],
                    ])->filter()->implode(' · '))
                    ->wrap()
                    ->hiddenFrom('md'),

                TextColumn::make('match')
                    ->label('Match')
                    ->state(fn (DibbsRfq $record) => $record->bestMatchReason())
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => DibbsRfqMatch::LABELS[$state] ?? null)
                    ->color(fn (?string $state) => $state === DibbsRfqMatch::REASON_CATALOG ? 'success' : 'info')
                    ->tooltip(fn (?string $state) => match ($state) {
                        DibbsRfqMatch::REASON_CATALOG => 'This NSN is in your parts catalog.',
                        DibbsRfqMatch::REASON_APPROVED_SOURCE => 'A manufacturer or supplier you buy from is a DLA-approved source for this NSN.',
                        default => null,
                    })
                    ->placeholder('—')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('matches_max_score', $direction))
                    ->visibleFrom('md'),

                TextColumn::make('return_by_date')
                    ->label('Return by')
                    ->date('M j')
                    ->description(fn (DibbsRfq $record) => static::closesIn($record))
                    ->color(fn (DibbsRfq $record) => static::urgencyColor($record))
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('solicitation_number')
                    ->label('Solicitation')
                    ->fontFamily('mono')
                    ->searchable()
                    ->copyable()
                    ->visibleFrom('md'),

                TextColumn::make('nsn')
                    ->label('NSN')
                    ->formatStateUsing(fn (DibbsRfq $record) => $record->formattedNsn())
                    ->fontFamily('mono')
                    ->searchable()
                    ->copyable()
                    ->visibleFrom('md'),

                TextColumn::make('nomenclature')
                    ->label('Item')
                    ->searchable()
                    ->wrap()
                    ->visibleFrom('md'),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->formatStateUsing(fn (DibbsRfq $record) => $record->quantity.' '.$record->unit_of_issue)
                    ->sortable()
                    ->visibleFrom('lg'),

                TextColumn::make('fsc')
                    ->label('FSC')
                    ->fontFamily('mono')
                    ->sortable()
                    ->visibleFrom('lg'),

                TextColumn::make('set_aside')
                    ->label('Set-aside')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => DibbsRfq::SET_ASIDES[$state] ?? $state)
                    ->color(fn (?string $state) => $state && $state !== 'N' ? 'success' : 'gray')
                    ->visibleFrom('lg'),

                TextColumn::make('amsc')
                    ->label('AMSC')
                    ->badge()
                    ->color(fn (?string $state) => in_array($state, DibbsRfq::OPEN_AMSC, true) ? 'success' : 'warning')
                    ->tooltip(fn (?string $state) => (DibbsRfq::AMSC_LABELS[$state] ?? 'Unknown').(in_array($state, DibbsRfq::OPEN_AMSC, true) ? ' (open competition)' : ' (approved sources only)'))
                    ->visibleFrom('md'),

                IconColumn::make('automated_award')
                    ->label('Auto award')
                    ->boolean()
                    ->tooltip('Eligible for DLA automated award: price and delivery decide, text remarks are not considered.')
                    ->visibleFrom('xl'),

                TextColumn::make('tracking_status')
                    ->label('Status')
                    ->state(fn (DibbsRfq $record) => $record->trackingStatus())
                    ->badge()
                    ->formatStateUsing(fn (string $state) => DibbsRfqTracking::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => DibbsRfqTracking::STATUS_COLORS[$state] ?? 'gray')
                    ->description(fn (DibbsRfq $record) => $record->tracking?->assignee?->name)
                    ->visibleFrom('md'),
            ])
            ->defaultSort('return_by_date', 'asc')
            ->persistFiltersInSession()
            ->filters([
                Filter::make('our_product_codes')
                    ->label('In our product codes')
                    ->default()
                    ->query(fn (Builder $query) => $query->inOurProductCodes()),

                Filter::make('still_open')
                    ->label('Hide closed')
                    ->default()
                    ->query(fn (Builder $query) => $query->stillOpen()),

                SelectFilter::make('match')
                    ->label('Catalog match')
                    ->options([
                        'any' => 'Any match',
                        DibbsRfqMatch::REASON_CATALOG => 'In our catalog',
                        DibbsRfqMatch::REASON_APPROVED_SOURCE => 'We buy from an approved source',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        null, '' => $query,
                        'any' => $query->whereHas('matches'),
                        default => $query->whereHas('matches', fn (Builder $m) => $m->where('reason', $data['value'])),
                    }),

                SelectFilter::make('closing_within')
                    ->label('Closing within')
                    ->options(['3' => '3 days', '7' => '7 days', '14' => '14 days'])
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereBetween('return_by_date', [today()->toDateString(), today()->addDays((int) $data['value'])->toDateString()])
                        : $query),

                SelectFilter::make('competition')
                    ->label('Competition')
                    ->options(['open' => 'Open (AMSC G, Z, L, O)', 'approved' => 'Approved sources only'])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'open' => $query->openCompetition(),
                        'approved' => $query->approvedSourceOnly(),
                        default => $query,
                    }),

                SelectFilter::make('set_aside')
                    ->label('Set-aside')
                    ->options(DibbsRfq::SET_ASIDES)
                    ->multiple(),

                SelectFilter::make('amsc')
                    ->label('AMSC')
                    ->options(collect(DibbsRfq::AMSC_LABELS)->mapWithKeys(fn ($label, $code) => [$code => "{$code} – {$label}"])->all())
                    ->multiple(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(DibbsRfqTracking::STATUSES)
                    ->multiple()
                    ->query(function (Builder $query, array $data) {
                        $statuses = $data['values'] ?? [];

                        if ($statuses === []) {
                            return $query;
                        }

                        // Untracked RFQs count as "new".
                        return $query->where(fn (Builder $q) => $q
                            ->whereHas('tracking', fn (Builder $t) => $t->whereIn('status', $statuses))
                            ->when(in_array('new', $statuses, true), fn (Builder $q) => $q->orWhereDoesntHave('tracking')));
                    }),

                TernaryFilter::make('automated_award')
                    ->label('Automated award'),

                Filter::make('new_today')
                    ->label('From the latest file')
                    ->query(fn (Builder $query) => $query->where('first_seen_file_id', DibbsFile::where('type', DibbsFile::TYPE_INDEX)
                        ->where('status', DibbsFile::STATUS_IMPORTED)
                        ->orderByDesc('posting_date')
                        ->value('id'))),

                Filter::make('quantity')
                    ->schema([
                        TextInput::make('min')->label('Min qty')->numeric(),
                        TextInput::make('max')->label('Max qty')->numeric(),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when(filled($data['min'] ?? null), fn (Builder $q) => $q->where('quantity', '>=', (int) $data['min']))
                        ->when(filled($data['max'] ?? null), fn (Builder $q) => $q->where('quantity', '<=', (int) $data['max']))),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    static::addToWorksheetAction(),
                    static::setStatusAction(),
                    static::assignAction(),
                    static::noteAction(),
                    static::addToCatalogAction(),
                    Action::make('openInDibbs')
                        ->label('Open in DIBBS')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (DibbsRfq $record) => $record->dibbsRecordUrl(), shouldOpenInNewTab: true),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_worksheet')
                        ->label('Add to quote worksheet')
                        ->icon('heroicon-o-calculator')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $records->each(fn (DibbsRfq $record) => DibbsRfqTracking::for($record)->startQuoting());

                            Notification::make()->title($records->count().' RFQ(s) added to the quote worksheet')->success()->send();
                        }),
                    static::bulkStatusAction('reviewing', 'Mark reviewing', 'heroicon-o-eye'),
                    static::bulkStatusAction('no_bid', 'Mark no bid', 'heroicon-o-no-symbol'),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('RFQ')
                ->columns(['default' => 1, 'sm' => 2, 'lg' => 3])
                ->schema([
                    TextEntry::make('solicitation_number')->label('Solicitation')->fontFamily('mono')->copyable(),
                    TextEntry::make('nsn')->label('NSN')->state(fn (DibbsRfq $record) => $record->formattedNsn())->fontFamily('mono')->copyable(),
                    TextEntry::make('nomenclature')->label('Item'),
                    TextEntry::make('quantity')->label('Quantity')->state(fn (DibbsRfq $record) => $record->quantity.' '.$record->unit_of_issue),
                    TextEntry::make('return_by_date')->label('Return by')->date('M j, Y')->helperText(fn (DibbsRfq $record) => static::closesIn($record))->color(fn (DibbsRfq $record) => static::urgencyColor($record)),
                    TextEntry::make('purchase_request')->label('Purchase request')->fontFamily('mono'),
                    TextEntry::make('set_aside')->label('Set-aside')->badge()->formatStateUsing(fn (?string $state, DibbsRfq $record) => (DibbsRfq::SET_ASIDES[$state] ?? $state).($state && $state !== 'N' && $record->set_aside_percent ? " ({$record->set_aside_percent}%)" : '')),
                    TextEntry::make('amsc')->label('AMSC')->badge()
                        ->formatStateUsing(fn (?string $state) => $state.' – '.(DibbsRfq::AMSC_LABELS[$state] ?? 'Unknown'))
                        ->color(fn (?string $state) => in_array($state, DibbsRfq::OPEN_AMSC, true) ? 'success' : 'warning')
                        ->helperText(fn (DibbsRfq $record) => $record->isOpenCompetition() ? 'Open competition.' : 'Approved sources only: offer an approved part (see below).'),
                    TextEntry::make('automated_award')->label('Award')->state(fn (DibbsRfq $record) => $record->automated_award ? 'Automated (price and delivery decide)' : 'Buyer evaluated'),
                    TextEntry::make('fsc')->label('FSC')->fontFamily('mono'),
                    TextEntry::make('buyer_code')->label('Buyer code')->fontFamily('mono'),
                    TextEntry::make('firstSeenFile.posting_date')->label('First posted')->date('M j, Y'),
                ]),

            Section::make('DIBBS')
                ->columns(['default' => 1, 'sm' => 2])
                ->schema([
                    TextEntry::make('record_link')->label('RFQ record')->state('Open in DIBBS')->url(fn (DibbsRfq $record) => $record->dibbsRecordUrl(), shouldOpenInNewTab: true)->color('primary'),
                    TextEntry::make('pdf_link')->label('Solicitation')->state(fn (DibbsRfq $record) => $record->solicitation_number.'.PDF')->url(fn (DibbsRfq $record) => $record->solicitationPdfUrl(), shouldOpenInNewTab: true)->color('primary'),
                    TextEntry::make('nsn_link')->label('NSN history')->state('Other RFQs for this NSN')->url(fn (DibbsRfq $record) => $record->dibbsNsnHistoryUrl(), shouldOpenInNewTab: true)->color('primary'),
                    TextEntry::make('quote_link')->label('Quote')->state('Quote form (DIBBS login)')->url(fn (DibbsRfq $record) => $record->dibbsQuoteFormUrl(), shouldOpenInNewTab: true)->color('primary'),
                ]),

            Section::make('Tracking')
                ->columns(['default' => 1, 'sm' => 2])
                ->schema([
                    TextEntry::make('tracking_status')->label('Status')->state(fn (DibbsRfq $record) => $record->trackingStatus())->badge()
                        ->formatStateUsing(fn (string $state) => DibbsRfqTracking::STATUSES[$state] ?? $state)
                        ->color(fn (string $state) => DibbsRfqTracking::STATUS_COLORS[$state] ?? 'gray'),
                    TextEntry::make('tracking.assignee.name')->label('Owner')->placeholder('Unassigned'),
                    TextEntry::make('tracking.notes')->label('Notes')->placeholder('No notes')->columnSpanFull()->prose(),
                ]),

            Section::make('Your catalog')
                ->description('Parts in your catalog with this NSN, with suppliers and the last price paid.')
                ->visible(fn (DibbsRfq $record) => $record->catalogParts()->isNotEmpty())
                ->schema([
                    RepeatableEntry::make('catalog')
                        ->hiddenLabel()
                        ->state(fn (DibbsRfq $record) => static::catalogSummary($record))
                        ->schema([
                            Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])->schema([
                                TextEntry::make('description')->label('Part'),
                                TextEntry::make('manufacturer')->label('Manufacturer / P/N'),
                                TextEntry::make('suppliers')->label('Suppliers')->placeholder('None linked'),
                                TextEntry::make('last_price')->label('Last price paid')->placeholder('No procurement history'),
                            ]),
                        ]),
                ]),

            Section::make('Approved sources')
                ->description('From DLA\'s approved source file for this NSN. For approved-source-only buys, the offer must be one of these parts. ✓ marks a CAGE that belongs to one of your manufacturers or suppliers.')
                ->schema([
                    RepeatableEntry::make('approvedSources')
                        ->hiddenLabel()
                        ->placeholder('None listed for this NSN.')
                        ->schema([
                            Grid::make(['default' => 1, 'sm' => 4])->schema([
                                TextEntry::make('cage_code')->label('CAGE')->fontFamily('mono')
                                    ->formatStateUsing(fn (string $state) => in_array(strtoupper($state), static::ourCageCodes(), true) ? "✓ {$state}" : $state)
                                    ->color(fn (string $state) => in_array(strtoupper($state), static::ourCageCodes(), true) ? 'success' : null),
                                TextEntry::make('part_number')->label('Part number')->fontFamily('mono')->copyable(),
                                TextEntry::make('company_name')->label('Company')->placeholder('—'),
                                TextEntry::make('ours')->label('Your source')
                                    ->state(fn (DibbsApprovedSource $record) => static::ourSourceName($record->cage_code))
                                    ->placeholder('—'),
                            ]),
                        ]),
                ]),

            Section::make('History')
                ->collapsible()
                ->schema([
                    RepeatableEntry::make('history')
                        ->hiddenLabel()
                        ->state(fn (DibbsRfq $record) => static::history($record))
                        ->placeholder('No activity yet.')
                        ->schema([
                            TextEntry::make('summary')->hiddenLabel(),
                        ]),
                ]),
        ]);
    }

    public static function addToCatalogAction(): Action
    {
        return Action::make('addToCatalog')
            ->label('Add NSN to catalog')
            ->icon('heroicon-o-plus-circle')
            ->color('gray')
            ->visible(fn (DibbsRfq $record) => $record->item_type === '1' && $record->catalogParts()->isEmpty())
            ->schema([
                Textarea::make('description')
                    ->required()
                    ->rows(2)
                    ->default(fn (DibbsRfq $record) => $record->nomenclature),
                Select::make('manufacturer_id')
                    ->label('Manufacturer')
                    ->options(fn () => Manufacturer::orderBy('name')->get()->mapWithKeys(fn (Manufacturer $m) => [$m->id => $m->name.($m->cage_code ? " ({$m->cage_code})" : '')]))
                    ->searchable()
                    ->required()
                    ->helperText('Missing? Add it under Manufacturers first. For approved-source buys, pick the approved manufacturer.'),
                TextInput::make('manufacturer_part_number')
                    ->label('Manufacturer part number')
                    ->helperText('For approved-source buys, use the approved part number listed below.'),
            ])
            ->action(function (DibbsRfq $record, array $data) {
                RebuildDibbsMatchesJob::withoutRebuilds(fn () => MilSpecPart::create([
                    'nsn' => MilSpecPartCatalogImporter::formatNsn($record->nsn),
                    'description' => $data['description'],
                    'manufacturer_id' => $data['manufacturer_id'],
                    'manufacturer_part_number' => $data['manufacturer_part_number'] ?: null,
                ]));

                app(DibbsRfqMatcher::class)->rebuild();
                $record->unsetRelation('matches');

                Notification::make()->title('Added to catalog')->body($record->formattedNsn().' is now in your parts catalog.')->success()->send();
            });
    }

    /**
     * @return array<int, array<string, ?string>>
     */
    protected static function catalogSummary(DibbsRfq $record): array
    {
        return $record->catalogParts()->map(function (MilSpecPart $part) {
            $last = $part->procurementHistories->first();

            return [
                'description' => $part->description,
                'manufacturer' => trim(($part->manufacturer?->name ?? '—').($part->manufacturer?->cage_code ? " ({$part->manufacturer->cage_code})" : '').($part->manufacturer_part_number ? ' · P/N '.$part->manufacturer_part_number : '')),
                'suppliers' => $part->suppliers->map(fn (Supplier $s) => $s->name.($s->pivot->supplier_part_number ? " ({$s->pivot->supplier_part_number})" : ''))->implode(', ') ?: null,
                'last_price' => $last ? '$'.number_format((float) $last->price, 2).' × '.$last->quantity.' on '.$last->acquisition_date?->format('M j, Y').($last->supplier ? ' from '.$last->supplier->name : '') : null,
            ];
        })->all();
    }

    /**
     * Upper-cased CAGE codes of our manufacturers and suppliers (once per request).
     *
     * @return array<int, string>
     */
    protected static function ourCageCodes(): array
    {
        return once(fn () => Manufacturer::whereNotNull('cage_code')->pluck('cage_code')
            ->merge(Supplier::whereNotNull('cage_code')->pluck('cage_code'))
            ->map(fn ($c) => strtoupper($c))
            ->unique()->values()->all());
    }

    protected static function ourSourceName(string $cage): ?string
    {
        $cage = strtoupper($cage);

        // Built by key assignment: all-digit CAGEs (e.g. 79227) become integer
        // keys, which Collection::merge() would renumber and lose.
        return once(function () {
            $names = [];

            foreach (Supplier::whereNotNull('cage_code')->get() as $supplier) {
                $names[strtoupper($supplier->cage_code)] = $supplier->name.' (supplier)';
            }

            foreach (Manufacturer::whereNotNull('cage_code')->get() as $manufacturer) {
                $names[strtoupper($manufacturer->cage_code)] = $manufacturer->name.' (manufacturer)';
            }

            return $names;
        })[$cage] ?? null;
    }

    public static function addToWorksheetAction(): Action
    {
        return Action::make('addToWorksheet')
            ->label('Add to quote worksheet')
            ->icon('heroicon-o-calculator')
            ->visible(fn (DibbsRfq $record) => ! in_array($record->trackingStatus(), ['quoting', 'quoted', 'won', 'lost'], true))
            ->action(function (DibbsRfq $record) {
                DibbsRfqTracking::for($record)->startQuoting();
                $record->unsetRelation('tracking');

                Notification::make()
                    ->title('Added to quote worksheet')
                    ->actions([Action::make('open')->label('Open worksheet')->url(DibbsQuoteWorksheet::getUrl())])
                    ->success()
                    ->send();
            });
    }

    public static function setStatusAction(): Action
    {
        return Action::make('setStatus')
            ->label('Set status')
            ->icon('heroicon-o-flag')
            ->schema([
                Select::make('status')
                    ->options(DibbsRfqTracking::STATUSES)
                    ->default(fn (DibbsRfq $record) => $record->trackingStatus())
                    ->required(),
            ])
            ->action(function (DibbsRfq $record, array $data) {
                DibbsRfqTracking::for($record)->update(['status' => $data['status']]);
                $record->unsetRelation('tracking');

                Notification::make()->title('Status: '.DibbsRfqTracking::STATUSES[$data['status']])->success()->send();
            });
    }

    public static function assignAction(): Action
    {
        return Action::make('assign')
            ->label('Assign owner')
            ->icon('heroicon-o-user')
            ->schema([
                Select::make('assigned_to')
                    ->label('Owner')
                    ->options(fn () => Admin::orderBy('name')->pluck('name', 'id'))
                    ->default(fn (DibbsRfq $record) => $record->tracking?->assigned_to)
                    ->placeholder('Unassigned'),
            ])
            ->action(function (DibbsRfq $record, array $data) {
                DibbsRfqTracking::for($record)->update(['assigned_to' => $data['assigned_to'] ?: null]);
                $record->unsetRelation('tracking');

                Notification::make()->title('Owner updated')->success()->send();
            });
    }

    public static function noteAction(): Action
    {
        return Action::make('note')
            ->label('Edit notes')
            ->icon('heroicon-o-pencil-square')
            ->schema([
                Textarea::make('notes')
                    ->rows(5)
                    ->default(fn (DibbsRfq $record) => $record->tracking?->notes),
            ])
            ->action(function (DibbsRfq $record, array $data) {
                DibbsRfqTracking::for($record)->update(['notes' => $data['notes'] ?: null]);
                $record->unsetRelation('tracking');

                Notification::make()->title('Notes saved')->success()->send();
            });
    }

    protected static function bulkStatusAction(string $status, string $label, string $icon): BulkAction
    {
        return BulkAction::make('bulk_'.$status)
            ->label($label)
            ->icon($icon)
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) use ($status) {
                $records->each(fn (DibbsRfq $record) => DibbsRfqTracking::for($record)->update(['status' => $status]));

                Notification::make()->title($records->count().' RFQ(s): '.DibbsRfqTracking::STATUSES[$status])->success()->send();
            });
    }

    /**
     * Activity on this RFQ's tracking row, newest first, as readable lines.
     *
     * @return array<int, array{summary: string}>
     */
    protected static function history(DibbsRfq $record): array
    {
        if (! $record->tracking) {
            return [];
        }

        return Activity::query()
            ->where('subject_type', DibbsRfqTracking::class)
            ->where('subject_id', $record->tracking->id)
            ->with('causer')
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(function (Activity $activity) {
                $changes = collect($activity->properties->get('attributes', []))
                    ->map(fn ($value, $field) => match ($field) {
                        'status' => 'status → '.(DibbsRfqTracking::STATUSES[$value] ?? $value),
                        'assigned_to' => 'owner → '.($value ? (Admin::find($value)?->name ?? "#{$value}") : 'unassigned'),
                        'notes' => 'notes edited',
                        default => "{$field} → {$value}",
                    })
                    ->implode(', ');

                return ['summary' => $activity->created_at->format('M j, Y g:i a').' · '.($activity->causer?->name ?? 'System').' · '.($changes ?: $activity->description)];
            })
            ->all();
    }

    protected static function closesIn(DibbsRfq $record): ?string
    {
        if (! $record->return_by_date) {
            return null;
        }

        $days = (int) today()->diffInDays($record->return_by_date, false);

        return match (true) {
            $days < 0 => 'Closed',
            $days === 0 => 'Closes today',
            $days === 1 => 'Closes tomorrow',
            default => "Closes in {$days} days",
        };
    }

    protected static function urgencyColor(DibbsRfq $record): ?string
    {
        if (! $record->return_by_date) {
            return null;
        }

        $days = (int) today()->diffInDays($record->return_by_date, false);

        return match (true) {
            $days < 0 => 'gray',
            $days <= 3 => 'danger',
            $days <= 7 => 'warning',
            default => null,
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDibbsRfqs::route('/'),
            'view' => Pages\ViewDibbsRfq::route('/{record}'),
        ];
    }
}
