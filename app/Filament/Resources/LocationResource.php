<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Filament\Resources\LocationResource\RelationManagers\ProductsRelationManager;
use App\Models\Location;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-map-pin';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Location Information')
                ->schema([
                    Select::make('company_id')
                        ->relationship('company', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live(),

                    Select::make('parent_id')
                        ->label('Parent Location')
                        ->options(fn (Get $get, ?Location $record) => Location::query()
                            ->when($get('company_id'), fn ($query, $companyId) => $query->where('company_id', $companyId))
                            ->when($record?->id, fn ($query, $recordId) => $query->whereKeyNot($recordId))
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Leave blank for a primary location. Select a parent to create a sublocation.'),

                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $operation, $state, callable $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->extraInputAttributes(['class' => 'font-mono'])
                        ->helperText('Auto-generated from location name'),
                ])
                ->columns(2),

            Section::make('Will call')
                ->description('Let buyers choose this location for counter pickup at checkout. Everything except the toggle is optional; buyers see only what you fill in.')
                ->schema([
                    Toggle::make('pickup_enabled')
                        ->label('Offer will call pickup here')
                        ->live()
                        ->columnSpanFull(),
                    TextInput::make('address.line1')->label('Street address')->maxLength(255),
                    TextInput::make('address.line2')->label('Suite / unit')->maxLength(255),
                    TextInput::make('address.city')->label('City')->maxLength(255),
                    TextInput::make('address.state')->label('State')->maxLength(50),
                    TextInput::make('address.postal_code')->label('ZIP')->maxLength(20),
                    TextInput::make('phone')->label('Counter phone')->tel()->maxLength(50),
                    Textarea::make('hours')
                        ->label('Pickup hours')
                        ->placeholder("Mon–Fri 7am–5pm\nSat 8am–noon")
                        ->rows(3)
                        ->maxLength(1000),
                    Textarea::make('pickup_instructions')
                        ->label('Pickup instructions')
                        ->placeholder('Use the will-call door on the north side and bring your pickup code.')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(fn (?Location $record) => ! $record?->pickup_enabled),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent')
                    ->placeholder('Primary')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('pickup_enabled')
                    ->label('Will call')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->badge()
                    ->color('gray')
                    ->fontFamily(FontFamily::Mono)
                    ->placeholder('--')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('locationProducts_count')
                    ->counts('locationProducts')
                    ->label('Products')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('children_count')
                    ->counts('children')
                    ->label('Sublocations')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('pickup_enabled')
                    ->label('Will call'),

                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Parent Location')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
