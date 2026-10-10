<?php

namespace App\Filament\Pages;

use App\Models\Admin;
use App\Services\NsnLookupService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use UnitEnum;

class FetchNsnData extends Page
{
    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCube;

    protected string $view = 'filament.pages.fetch-nsn-data';

    protected static UnitEnum|string|null $navigationGroup = 'NSN Procurement';

    protected static ?string $title = 'PUB LOG Lookup';

    protected static ?int $navigationSort = 1;

    public ?array $formData = ['type' => 'nsn', 'query' => ''];

    #[Locked]
    public ?array $result = null;

    public static function canAccess(): bool
    {
        return Auth::guard('admin')->user() instanceof Admin;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Search the PUB LOG catalog')
                ->description('Find NSNs, part references and dated company snapshots. Searching does not save or change your curated parts.')
                ->schema([
                    Select::make('type')->label('Search by')->options(['nsn' => 'NSN', 'niin' => 'NIIN', 'part' => 'Part number', 'cage' => 'CAGE code'])->required()->native(),
                    TextInput::make('query')->label('Number or code')->required()->maxLength(255)
                        ->helperText('NSN: 13 digits; NIIN: 9 digits; CAGE: 5 letters/digits. Standard NSN/NIIN dashes are accepted. Part numbers match exactly apart from letter case and surrounding spaces.')
                        ->autofocus(),
                ])->columns(2),
        ])->statePath('formData');
    }

    public function fetchNsnData(): void
    {
        abort_unless(static::canAccess(), 403);
        $data = $this->form->getState();
        $this->result = app(NsnLookupService::class)->lookup($data['type'], $data['query']);
    }

    public function changePage(int $direction, bool $relationships = false): void
    {
        abort_unless(static::canAccess(), 403);
        if (! $this->result || ! $this->result['source'] || ! in_array($direction, [-1, 1], true)) {
            return;
        }
        $key = $relationships ? 'relationship_page' : 'page';
        $more = $relationships ? 'relationships_have_more' : 'has_more';
        if (($direction === 1 && ! $this->result[$more]) || $this->result[$key] + $direction < 1) {
            return;
        }
        $pages = ['page' => $this->result['page'], 'relationship_page' => $this->result['relationship_page']];
        $pages[$key] += $direction;
        $this->result = app(NsnLookupService::class)->lookup($this->result['type'], $this->result['query'], $pages['page'], $this->result['source']['import_id'], $pages['relationship_page']);
    }

    public function inspectItem(string $niin): void
    {
        abort_unless(static::canAccess(), 403);
        if (! $this->result || ! in_array($niin, array_column($this->result['items'], 'niin'), true)) {
            return;
        }
        $this->formData = ['type' => 'niin', 'query' => $niin];
        $this->result = app(NsnLookupService::class)->lookup('niin', $niin, snapshotId: $this->result['source']['import_id']);
    }
}
