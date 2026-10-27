<?php

namespace App\Filament\Pages;

use App\Models\DibbsQuotingProfile;
use BackedEnum;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

/**
 * The company's standing DIBBS quote representations (ai/modules/dibbs-rfq-tracking, Phase 6).
 *
 * Nothing is pre-selected: each answer is a representation to the Government
 * and must come from the person entering it, who confirms it on every save.
 */
class DibbsQuotingProfilePage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-identification';

    protected static UnitEnum|string|null $navigationGroup = 'Automation';

    protected static ?string $navigationLabel = 'DIBBS Quoting Profile';

    protected static ?string $title = 'DIBBS Quoting Profile';

    protected static ?string $slug = 'dibbs-quoting-profile';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.dibbs-quoting-profile';

    public ?array $formData = [];

    public function mount(): void
    {
        $this->form->fill(DibbsQuotingProfile::current()->only(DibbsQuotingProfile::REPRESENTATION_FIELDS) + ['confirm' => false]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Who is quoting')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextInput::make('quoter_cage')->label('Quoter CAGE (col 6)')->helperText('The CAGE that submits the quote. Must match your DIBBS registration.')->required()->regex('/^[0-9A-Za-z]{5}$/')->maxLength(5),
                        TextInput::make('quote_for_cage')->label('Quote-for CAGE (col 7)')->helperText('Usually the same. If different, that CAGE must have authorized the quoter in DIBBS.')->required()->regex('/^[0-9A-Za-z]{5}$/')->maxLength(5),
                    ]),
                Section::make('Representations (made on every quote)')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        Select::make('small_business_code')->label('Small business and other contractor representation (col 13)')->options(DibbsQuotingProfile::SMALL_BUSINESS_CODES)->required(),
                        Select::make('affirmative_action_code')->label('Affirmative action compliance (col 21)')->options(DibbsQuotingProfile::AFFIRMATIVE_ACTION_CODES)->required(),
                        Select::make('previous_contracts_code')->label('Previous contracts and compliance reports (col 22)')->options(DibbsQuotingProfile::PREVIOUS_CONTRACTS_CODES)->required(),
                        Select::make('adr_code')->label('Alternate disputes resolution, DLAD 5452.233-9001 (col 23)')->options(DibbsQuotingProfile::ADR_CODES)->required(),
                        Select::make('child_labor_code')->label('Child labor certification, FAR 52.222-18 (col 120)')->options(DibbsQuotingProfile::CHILD_LABOR_CODES)->required()
                            ->helperText('"Yes" requires a bid with exception, which batch export does not support; quote those in DIBBS.'),
                    ]),
                Section::make('Defaults for each quote (can be changed per quote)')
                    ->columns(['default' => 1, 'md' => 3])
                    ->schema([
                        Select::make('default_manufacturer_dealer')->label('Manufacturer or dealer (col 102)')->options(DibbsQuotingProfile::MANUFACTURER_DEALER_CODES)->required(),
                        Select::make('default_hazardous_material')->label('Hazardous material, FAR 52.223-3 (col 65)')->options(DibbsQuotingProfile::HAZARDOUS_MATERIAL_CODES)->required(),
                        Select::make('default_material_code')->label('Material, FAR 52.211-5 (col 67)')->options(DibbsQuotingProfile::MATERIAL_CODES)->required(),
                    ]),
                Section::make('Confirmation')
                    ->schema([
                        Checkbox::make('confirm')
                            ->label('I confirm these representations are true and current for this company, and that I am authorized to make them on DIBBS quotes.')
                            ->accepted()
                            ->required(),
                    ]),
            ])
            ->statePath('formData');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $profile = DibbsQuotingProfile::current();

        $profile->fill(collect($data)->only(DibbsQuotingProfile::REPRESENTATION_FIELDS)->map(fn ($v, $k) => str_ends_with($k, '_cage') ? strtoupper($v) : $v)->all());
        $profile->fill(['confirmed_by' => auth('admin')->id(), 'confirmed_at' => now()]);
        $profile->save();

        $this->form->fill($profile->only(DibbsQuotingProfile::REPRESENTATION_FIELDS) + ['confirm' => false]);

        Notification::make()->title('Quoting profile saved and confirmed')->success()->send();
    }

    public function getProfile(): DibbsQuotingProfile
    {
        return DibbsQuotingProfile::current()->load('confirmer');
    }
}
