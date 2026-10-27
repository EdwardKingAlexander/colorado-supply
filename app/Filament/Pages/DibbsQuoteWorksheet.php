<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DibbsRfqResource;
use App\Models\DibbsQuotingProfile;
use App\Models\DibbsRfq;
use App\Models\DibbsRfqTracking;
use App\Services\Dibbs\BatchQuoteExporter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Price DIBBS RFQs and export one batch quote upload (ai/modules/dibbs-rfq-tracking, Phase 6).
 *
 * Lists RFQs in "quoting" status. Each row shows exactly what still blocks it;
 * export takes only ready rows, after an explicit review of the representations.
 * The app never submits: the person uploads the file in DIBBS.
 */
class DibbsQuoteWorksheet extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calculator';

    protected static UnitEnum|string|null $navigationGroup = 'Automation';

    protected static ?string $navigationLabel = 'DIBBS Quote Worksheet';

    protected static ?string $title = 'DIBBS Quote Worksheet';

    protected static ?string $slug = 'dibbs-quote-worksheet';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.dibbs-quote-worksheet';

    public function table(Table $table): Table
    {
        return $table
            ->query(DibbsRfqTracking::query()
                ->where('status', 'quoting')
                ->where('company_id', DibbsRfqTracking::currentCompanyId())
                ->with(['rfq.quoteTemplates', 'rfq.approvedSources']))
            ->defaultSort(fn (Builder $query) => $query->orderBy(
                DibbsRfq::select('return_by_date')->whereColumn('dibbs_rfqs.id', 'dibbs_rfq_trackings.dibbs_rfq_id')
            ))
            ->emptyStateHeading('Nothing to quote yet')
            ->emptyStateDescription('On DIBBS RFQs, use "Add to quote worksheet" on the RFQs you want to price.')
            ->columns([
                TextColumn::make('rfq.nomenclature')
                    ->label('RFQ')
                    ->description(fn (DibbsRfqTracking $record) => collect([
                        $record->rfq->formattedNsn(),
                        trim($record->rfq->quantity.' '.$record->rfq->unit_of_issue),
                        $record->rfq->solicitation_number,
                        'due '.$record->rfq->return_by_date?->format('M j'),
                    ])->filter()->implode(' · '))
                    ->url(fn (DibbsRfqTracking $record) => DibbsRfqResource::getUrl('view', ['record' => $record->dibbs_rfq_id]))
                    ->wrap(),

                TextInputColumn::make('unit_price')
                    ->label('Unit price ($)')
                    ->type('number')
                    ->step('0.00001')
                    ->rules(['nullable', 'numeric', 'min:0', 'max:9999999.99999']),

                TextInputColumn::make('delivery_days')
                    ->label('Delivery days')
                    ->type('number')
                    ->rules(['nullable', 'integer', 'min:1', 'max:9999']),

                TextColumn::make('part_offered')
                    ->label('Part offered')
                    ->state(fn (DibbsRfqTracking $record) => $record->part_offered_cage
                        ? $record->part_offered_cage.' · '.$record->part_offered_number
                        : null)
                    ->placeholder('—')
                    ->visibleFrom('lg'),

                TextColumn::make('readiness')
                    ->label('Ready?')
                    ->state(fn (DibbsRfqTracking $record) => $this->problemsFor($record) === [] ? 'Ready' : count($this->problemsFor($record)).' to fix')
                    ->badge()
                    ->color(fn (string $state) => $state === 'Ready' ? 'success' : 'warning')
                    ->description(fn (DibbsRfqTracking $record) => implode(' ', $this->problemsFor($record)) ?: null)
                    ->wrap(),
            ])
            ->recordActions([
                ActionGroup::make([
                    $this->editQuoteAction(),
                    Action::make('remove')
                        ->label('Remove from worksheet')
                        ->icon('heroicon-o-x-mark')
                        ->color('gray')
                        ->action(fn (DibbsRfqTracking $record) => $record->update(['status' => 'reviewing'])),
                ]),
            ])
            ->headerActions([
                $this->exportAction(),
            ]);
    }

    protected function editQuoteAction(): Action
    {
        return Action::make('editQuote')
            ->label('Edit quote')
            ->icon('heroicon-o-pencil-square')
            ->fillForm(fn (DibbsRfqTracking $record) => $record->only([
                'unit_price', 'delivery_days', 'manufacturer_dealer', 'manufacturing_source_cage', 'part_offered_code',
                'part_offered_cage', 'part_offered_number', 'quality_code', 'hazardous_material', 'material_code',
            ]) + ['approved_part' => null])
            ->schema(fn (DibbsRfqTracking $record) => [
                Grid::make(['default' => 1, 'sm' => 2])->schema([
                    TextInput::make('unit_price')->label('Unit price ($)')->numeric()->minValue(0)->maxValue(9999999.99999)->step('0.00001'),
                    TextInput::make('delivery_days')->label('Delivery days after order')->integer()->minValue(1)->maxValue(9999),
                    Select::make('manufacturer_dealer')->label('Manufacturer or dealer')->options(DibbsQuotingProfile::MANUFACTURER_DEALER_CODES)->placeholder('Profile default'),
                    TextInput::make('manufacturing_source_cage')->label('Actual manufacturer CAGE (dealers)')->maxLength(5),
                    Select::make('hazardous_material')->label('Hazardous material')->options(DibbsQuotingProfile::HAZARDOUS_MATERIAL_CODES)->placeholder('Profile default'),
                    Select::make('material_code')->label('Material')->options(DibbsQuotingProfile::MATERIAL_CODES)->placeholder('Profile default'),
                ]),
                Select::make('approved_part')
                    ->label('Pick an approved source')
                    ->options($record->rfq->approvedSources->mapWithKeys(fn ($s) => ["{$s->cage_code}|{$s->part_number}" => "{$s->cage_code} · {$s->part_number}".($s->company_name ? " ({$s->company_name})" : '')]))
                    ->placeholder($record->rfq->approvedSources->isEmpty() ? 'None listed for this NSN' : 'Choose to fill the part fields below')
                    ->live()
                    ->afterStateUpdated(function (?string $state, callable $set) {
                        if ($state) {
                            [$cage, $part] = explode('|', $state, 2);
                            $set('part_offered_code', '1');
                            $set('part_offered_cage', $cage);
                            $set('part_offered_number', $part);
                            $set('manufacturing_source_cage', $cage);
                        }
                    })
                    ->dehydrated(false),
                Grid::make(['default' => 1, 'sm' => 3])->schema([
                    Select::make('part_offered_code')->label('Part offered')->options(BatchQuoteExporter::SUPPORTED_PART_OFFERED),
                    TextInput::make('part_offered_cage')->label('Offered CAGE')->maxLength(5),
                    TextInput::make('part_offered_number')->label('Offered part number')->maxLength(40),
                ]),
                Select::make('quality_code')
                    ->label('Manufacturing facility\'s higher-level quality system')
                    ->options(BatchQuoteExporter::QUALITY_CODE_LABELS)
                    ->helperText('Only needed when the RFQ requires AS9100, ISO 9001:2015 or AS9003.'),
            ])
            ->action(function (DibbsRfqTracking $record, array $data) {
                $record->update(collect($data)->except('approved_part')->map(fn ($v, $k) => str_ends_with($k, '_cage') && $v ? strtoupper($v) : ($v === '' ? null : $v))->all());

                Notification::make()->title('Quote updated')->success()->send();
            });
    }

    protected function exportAction(): Action
    {
        return Action::make('reviewAndExport')
            ->label('Review & export')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->modalHeading('Review and export batch quote')
            ->modalDescription(fn () => new HtmlString($this->reviewSummaryHtml()))
            ->modalSubmitActionLabel('Export file')
            ->schema([
                Checkbox::make('attest')
                    ->label('I have reviewed every quote and the representations above. They are accurate, and I am authorized to submit them for this company. I will upload the file to DIBBS myself.')
                    ->accepted()
                    ->required(),
            ])
            ->disabled(fn () => $this->readyTrackings()->isEmpty())
            ->action(function (): ?StreamedResponse {
                $ready = $this->readyTrackings();

                if ($ready->isEmpty()) {
                    Notification::make()->title('Nothing ready to export')->warning()->send();

                    return null;
                }

                $exporter = app(BatchQuoteExporter::class);
                $result = $exporter->export($ready, DibbsQuotingProfile::current(), (int) auth('admin')->id());

                Notification::make()
                    ->title('Batch quote exported')
                    ->body("{$result['reference']}: {$result['lines']} line(s) in ".count($result['files']).' file(s). Upload in DIBBS: Quoting → Batch Quote Upload.')
                    ->success()
                    ->persistent()
                    ->send();

                [$name, $contents] = count($result['files']) === 1
                    ? [array_key_first($result['files']), reset($result['files'])]
                    : [strtolower($result['reference']).'.zip', $exporter->zip($result['files'])];

                return response()->streamDownload(fn () => print ($contents), $name, [
                    'Content-Type' => str_ends_with($name, '.zip') ? 'application/zip' : 'text/plain; charset=ISO-8859-1',
                ]);
            });
    }

    /** Per-request cache of problems, keyed by tracking id + last update (not persisted by Livewire). */
    protected array $problemCache = [];

    /**
     * @return array<int, string>
     */
    public function problemsFor(DibbsRfqTracking $tracking): array
    {
        $key = $tracking->id.'@'.$tracking->updated_at?->getTimestamp();

        return $this->problemCache[$key] ??= app(BatchQuoteExporter::class)->problems($tracking, DibbsQuotingProfile::current());
    }

    /**
     * @return Collection<int, DibbsRfqTracking>
     */
    protected function readyTrackings(): Collection
    {
        $profile = DibbsQuotingProfile::current();
        $exporter = app(BatchQuoteExporter::class);

        return DibbsRfqTracking::query()
            ->where('status', 'quoting')
            ->where('company_id', DibbsRfqTracking::currentCompanyId())
            ->with('rfq.quoteTemplates')
            ->get()
            ->filter(fn (DibbsRfqTracking $t) => $exporter->problems($t, $profile) === [])
            ->values();
    }

    protected function reviewSummaryHtml(): string
    {
        $profile = DibbsQuotingProfile::current();
        $ready = $this->readyTrackings();
        $all = DibbsRfqTracking::query()->where('status', 'quoting')->where('company_id', DibbsRfqTracking::currentCompanyId())->count();
        $e = fn (?string $v) => e((string) $v);

        if (! $profile->isConfirmed()) {
            return '<p>The <a class="underline" href="'.DibbsQuotingProfilePage::getUrl().'">quoting profile</a> must be completed and confirmed before exporting.</p>';
        }

        $rows = [
            'Quoter / quote-for CAGE' => $profile->quoter_cage.' / '.$profile->quote_for_cage,
            'Small business representation' => DibbsQuotingProfile::SMALL_BUSINESS_CODES[$profile->small_business_code],
            'Affirmative action' => DibbsQuotingProfile::AFFIRMATIVE_ACTION_CODES[$profile->affirmative_action_code],
            'Previous contracts / reports' => DibbsQuotingProfile::PREVIOUS_CONTRACTS_CODES[$profile->previous_contracts_code],
            'Alternate disputes resolution' => DibbsQuotingProfile::ADR_CODES[$profile->adr_code],
            'Child labor certification' => DibbsQuotingProfile::CHILD_LABOR_CODES[$profile->child_labor_code],
            'End product' => 'D – Domestic end product (every line)',
            'Bid type' => 'BI – Bid without exception, on DLA\'s FOB, inspection, quantity and packaging terms',
        ];

        $lines = $ready->map(fn (DibbsRfqTracking $t) => '<li>'.$e($t->rfq->solicitation_number).' · '.$e($t->rfq->formattedNsn()).' · $'.$e(app(BatchQuoteExporter::class)->formatPrice((string) $t->unit_price)).' · '.$e((string) $t->delivery_days).' days · hazardous: '.$e($t->hazardous_material ?? $profile->default_hazardous_material).'</li>')->implode('');

        return '<p><strong>'.$ready->count().' of '.$all.' quotes are ready</strong>; the rest stay on the worksheet. Files hold at most '.BatchQuoteExporter::MAX_LINES_PER_FILE.' lines (DIBBS limit).</p>'
            .'<dl class="mt-3 grid grid-cols-1 gap-1 text-sm">'.collect($rows)->map(fn ($v, $k) => '<div><dt class="inline font-semibold">'.$e($k).':</dt> <dd class="inline">'.$e($v).'</dd></div>')->implode('').'</dl>'
            .'<ul class="mt-3 list-disc pl-5 text-sm">'.$lines.'</ul>';
    }
}
