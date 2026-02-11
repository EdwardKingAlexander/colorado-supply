<?php

namespace App\Filament\Resources\CRM;

use App\Filament\Resources\CRM\OpportunityResource\Pages;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Stage;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;

class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    protected static string | \UnitEnum | null $navigationGroup = 'CRM';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 30;

    protected static ?string $pluralLabel = 'Opportunities';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Opportunity Details')->columns(3)->schema([
                Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('pipeline_id')
                    ->label('Pipeline')
                    ->options(Pipeline::pluck('name', 'id'))
                    ->default(Pipeline::where('is_default', true)->first()?->id)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn(Set $set) => $set('stage_id', null)),

                Select::make('stage_id')
                    ->label('Stage')
                    ->options(fn(Get $get) => Stage::where('pipeline_id', $get('pipeline_id'))->orderBy('position')->pluck('name', 'id'))
                    ->required()
                    ->disabled(fn(Get $get) => !$get('pipeline_id')),

                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),

            Section::make('Financial Details')->columns(3)->schema([
                TextInput::make('amount')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->required(),

                Select::make('currency')
                    ->options([
                        'USD' => 'USD',
                        'CAD' => 'CAD',
                        'EUR' => 'EUR',
                        'GBP' => 'GBP',
                    ])
                    ->default('USD')
                    ->required(),

                TextInput::make('probability_override')
                    ->label('Probability Override (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->helperText('Override the stage default probability'),
            ]),

            Section::make('Timeline & Ownership')->columns(2)->schema([
                DatePicker::make('expected_close_date')
                    ->label('Expected Close Date')
                    ->displayFormat('M d, Y')
                    ->native(false),

                Select::make('owner_id')
                    ->label('Owner')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn() => auth()->id())
                    ->required(),

                Select::make('source')
                    ->options([
                        'Website' => 'Website',
                        'Referral' => 'Referral',
                        'Cold Call' => 'Cold Call',
                        'Trade Show' => 'Trade Show',
                        'Direct Mail' => 'Direct Mail',
                        'Existing Customer' => 'Existing Customer',
                    ])
                    ->searchable(),

                Select::make('score')
                    ->label('Priority Score')
                    ->options([
                        1 => '1 - Low',
                        2 => '2 - Medium-Low',
                        3 => '3 - Medium',
                        4 => '4 - Medium-High',
                        5 => '5 - High',
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pipeline.name')
                    ->badge()
                    ->color('gray'),

                BadgeColumn::make('stage.name')
                    ->label('Stage')
                    ->colors([
                        'success' => fn($record) => $record->stage?->is_won,
                        'danger' => fn($record) => $record->stage?->is_lost,
                        'warning' => fn($record) => $record->stage?->forecast_category === 'Commit',
                        'primary' => fn($record) => $record->stage?->forecast_category === 'BestCase',
                        'gray' => fn($record) => $record->stage?->forecast_category === 'Pipeline',
                    ]),

                TextColumn::make('amount')
                    ->money('usd')
                    ->sortable(),

                TextColumn::make('expected_close_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('owner.name')
                    ->label('Owner'),

                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'won',
                        'danger' => 'lost',
                        'primary' => 'open',
                    ]),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpportunities::route('/'),
            'create' => Pages\CreateOpportunity::route('/create'),
            'view' => Pages\ViewOpportunity::route('/{record}'),
            'edit' => Pages\EditOpportunity::route('/{record}/edit'),
        ];
    }
}
