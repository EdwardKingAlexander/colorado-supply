<?php

namespace App\Filament\Pages;

use App\Jobs\RebuildDibbsMatchesJob;
use App\Models\DibbsRfqMatch;
use App\Models\Manufacturer;
use App\Models\SamCageLookup;
use App\Models\Supplier;
use App\Services\Dibbs\DibbsRfqMatcher;
use App\Services\Sam\CageDirectory;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use UnitEnum;

/**
 * Which manufacturers DLA approves most often on open RFQs in our product codes
 * (ai/modules/dibbs-rfq-tracking, Phase 8). Adding one as a manufacturer we can
 * buy from turns on approved-source matching for its RFQs.
 */
class DibbsApprovedSourcesReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static UnitEnum|string|null $navigationGroup = 'Automation';

    protected static ?string $navigationLabel = 'DIBBS Approved Sources';

    protected static ?string $title = 'DIBBS Approved Sources';

    protected static ?string $slug = 'dibbs-approved-sources';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.dibbs-approved-sources';

    /** Rows shown: one SAM.gov lookup covers them all. */
    public const TOP = 100;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $search): Collection => $this->rows()
                ->when(filled($search), fn (Collection $rows) => $rows->filter(fn (array $r) => Str::contains(
                    Str::lower($r['cage_code'].' '.$r['company']), Str::lower($search),
                ))))
            ->paginated(false)
            ->searchable()
            ->columns([
                TextColumn::make('rank')->label('#'),
                TextColumn::make('company')
                    ->label('Company')
                    ->weight('medium')
                    ->description(fn (array $record) => collect([$record['cage_code'], $record['location'], $record['status']])->filter()->implode(' · '))
                    ->wrap(),
                TextColumn::make('open_rfqs')->label('Open RFQs')->numeric(),
                TextColumn::make('nsns')->label('NSNs')->numeric()->visibleFrom('md'),
                TextColumn::make('top_fscs')->label('Main FSCs')->visibleFrom('lg'),
                TextColumn::make('ours')
                    ->label('You buy from')
                    ->badge()
                    ->placeholder('—')
                    ->color('success'),
            ])
            ->recordActions([
                Action::make('addManufacturer')
                    ->label('Add as manufacturer')
                    ->icon('heroicon-o-plus-circle')
                    ->visible(fn (array $record) => $record['ours'] === null)
                    ->requiresConfirmation()
                    ->modalHeading(fn (array $record) => 'Add '.$record['company'].' as a manufacturer?')
                    ->modalDescription('Only do this if you can buy their parts (directly or through a distributor). DIBBS RFQs where they are an approved source will then match, and approved-source quotes can offer their exact part numbers.')
                    ->action(function (array $record) {
                        RebuildDibbsMatchesJob::withoutRebuilds(fn () => Manufacturer::create([
                            'name' => $record['company_name'] ?? $record['cage_code'],
                            'cage_code' => $record['cage_code'],
                        ]));

                        app(DibbsRfqMatcher::class)->rebuild();
                        $matched = DibbsRfqMatch::where('reason', DibbsRfqMatch::REASON_APPROVED_SOURCE)
                            ->where('matchable_type', Manufacturer::class)
                            ->whereIn('matchable_id', Manufacturer::where('cage_code', $record['cage_code'])->select('id'))
                            ->count();

                        Notification::make()
                            ->title('Added '.($record['company_name'] ?? $record['cage_code']))
                            ->body("{$matched} DIBBS RFQ(s) now match as approved-source buys.")
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('lookupNames')
                    ->label('Look up company names')
                    ->icon('heroicon-o-magnifying-glass')
                    ->tooltip('One SAM.gov request for up to 100 companies. Names are cached for 30 days.')
                    ->action(function () {
                        try {
                            $result = app(CageDirectory::class)->lookup($this->rows()->pluck('cage_code')->all());
                        } catch (RuntimeException $e) {
                            Notification::make()->title('Lookup failed')->body($e->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()
                            ->title($result['requests'] ? "Looked up {$result['requested']} companies" : 'All names already looked up')
                            ->body($result['requests'] ? "{$result['found']} found in SAM.gov ({$result['requests']} request(s))." : null)
                            ->success()
                            ->send();
                    }),
            ]);
    }

    /**
     * @return Collection<string, array<string, mixed>> keyed by CAGE
     */
    public function rows(): Collection
    {
        $codes = config('sam_opportunities.psc_codes', []);
        $groups = array_merge(config('sam_opportunities.psc_groups', []), config('sam_opportunities.psc_groups_optional', []));

        $ranked = DB::table('dibbs_approved_sources as s')
            ->join('dibbs_rfqs as r', 'r.nsn', '=', 's.nsn')
            ->where(fn ($q) => $q->whereNull('r.return_by_date')->orWhere('r.return_by_date', '>=', today()->toDateString()))
            ->where(fn ($q) => $q->whereIn('r.fsc', $codes)->orWhereIn(DB::raw('substr(r.fsc, 1, 2)'), $groups))
            ->groupBy('s.cage_code')
            ->select('s.cage_code', DB::raw('count(distinct r.id) as open_rfqs'), DB::raw('count(distinct s.nsn) as nsns'), DB::raw('max(s.company_name) as file_name'))
            ->orderByDesc('open_rfqs')
            ->limit(self::TOP)
            ->get();

        $cages = $ranked->pluck('cage_code')->map(fn ($c) => strtoupper($c))->all();
        $lookups = SamCageLookup::whereIn('cage_code', $cages)->get()->keyBy('cage_code');
        $ours = $this->ourSources();

        $fscs = DB::table('dibbs_approved_sources as s')
            ->join('dibbs_rfqs as r', 'r.nsn', '=', 's.nsn')
            ->whereIn('s.cage_code', $cages)
            ->where(fn ($q) => $q->whereNull('r.return_by_date')->orWhere('r.return_by_date', '>=', today()->toDateString()))
            ->select('s.cage_code', 'r.fsc', DB::raw('count(distinct r.id) as n'))
            ->groupBy('s.cage_code', 'r.fsc')
            ->get()
            ->groupBy('cage_code');

        // Keys are CAGE codes. All-digit CAGEs become integer keys; that's fine
        // as long as they're unique and stable (Filament uses them as record ids).
        return $ranked->values()->mapWithKeys(function ($row, $i) use ($lookups, $ours, $fscs) {
            $cage = strtoupper($row->cage_code);
            $lookup = $lookups->get($cage);
            $name = $lookup?->displayName() ?: ($row->file_name ?: null);

            return [$cage => [
                'rank' => $i + 1,
                'cage_code' => $cage,
                'company_name' => $name,
                'company' => $name ?? ($lookup && ! $lookup->found ? 'Not found in SAM.gov' : 'Name not looked up yet'),
                'location' => $lookup?->location(),
                'status' => $lookup?->found ? $lookup->registration_status : null,
                'open_rfqs' => (int) $row->open_rfqs,
                'nsns' => (int) $row->nsns,
                'top_fscs' => collect($fscs->get($row->cage_code, []))->sortByDesc('n')->take(3)->pluck('fsc')->implode(', '),
                'ours' => $ours[$cage] ?? null,
            ]];
        });
    }

    /**
     * @return array<string, string> CAGE => 'Manufacturer' | 'Supplier'
     */
    protected function ourSources(): array
    {
        $ours = [];

        foreach (Supplier::whereNotNull('cage_code')->pluck('cage_code') as $cage) {
            $ours[strtoupper($cage)] = 'Supplier';
        }

        foreach (Manufacturer::whereNotNull('cage_code')->pluck('cage_code') as $cage) {
            $ours[strtoupper($cage)] = 'Manufacturer';
        }

        return $ours;
    }
}
