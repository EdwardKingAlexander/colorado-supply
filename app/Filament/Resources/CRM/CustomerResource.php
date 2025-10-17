<?php

namespace App\Filament\Resources\CRM;

use App\Filament\Resources\CRM\CustomerResource\Pages;
use App\Filament\Resources\CRM\CustomerResource\RelationManagers;
use App\Models\Customer;
use App\Services\Google\PlacesAutocompleteService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string | \UnitEnum | null $navigationGroup = 'CRM';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Customer Information')->columns(2)->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('email')
                    ->email()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),

                TextInput::make('company')
                    ->maxLength(255),

                TextInput::make('website')
                    ->url()
                    ->maxLength(255),

                Select::make('owner_id')
                    ->label('Owner')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn() => auth()->id()),
            ]),

            Section::make('Addresses')
                ->columns(2)
                ->schema([
                    Section::make('Billing Address')
                        ->columns(2)
                        ->schema([
                            TextInput::make('billing_address_lookup')
                                ->label('Billing Address Search')
                                ->placeholder('Start typing to autocomplete')
                                ->live(debounce: 750)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => static::hydrateAddressFromLookup($set, $state, 'billing_address'))
                                ->dehydrated(false)
                                ->columnSpanFull(),
                            TextInput::make('billing_address.street')
                                ->label('Street')
                                ->maxLength(255)
                                ->columnSpanFull(),
                            TextInput::make('billing_address.line2')
                                ->label('Apartment / Suite')
                                ->maxLength(255)
                                ->columnSpanFull(),
                            TextInput::make('billing_address.city')
                                ->label('City')
                                ->maxLength(255),
                            TextInput::make('billing_address.state')
                                ->label('State / Province')
                                ->maxLength(255),
                            TextInput::make('billing_address.zip')
                                ->label('Postal Code')
                                ->maxLength(20),
                            TextInput::make('billing_address.country')
                                ->label('Country')
                                ->maxLength(255),
                            Hidden::make('billing_address.formatted'),
                            Hidden::make('billing_address.place_id'),
                            Hidden::make('billing_address.latitude'),
                            Hidden::make('billing_address.longitude'),
                        ]),
                    Section::make('Shipping Address')
                        ->columns(2)
                        ->schema([
                            TextInput::make('shipping_address_lookup')
                                ->label('Shipping Address Search')
                                ->placeholder('Start typing to autocomplete')
                                ->live(debounce: 750)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => static::hydrateAddressFromLookup($set, $state, 'shipping_address'))
                                ->dehydrated(false)
                                ->columnSpanFull(),
                            TextInput::make('shipping_address.street')
                                ->label('Street')
                                ->maxLength(255)
                                ->columnSpanFull(),
                            TextInput::make('shipping_address.line2')
                                ->label('Apartment / Suite')
                                ->maxLength(255)
                                ->columnSpanFull(),
                            TextInput::make('shipping_address.city')
                                ->label('City')
                                ->maxLength(255),
                            TextInput::make('shipping_address.state')
                                ->label('State / Province')
                                ->maxLength(255),
                            TextInput::make('shipping_address.zip')
                                ->label('Postal Code')
                                ->maxLength(20),
                            TextInput::make('shipping_address.country')
                                ->label('Country')
                                ->maxLength(255),
                            Hidden::make('shipping_address.formatted'),
                            Hidden::make('shipping_address.place_id'),
                            Hidden::make('shipping_address.latitude'),
                            Hidden::make('shipping_address.longitude'),
                        ]),
                ])
                ->collapsible(),
        ]);
    }

    protected static function hydrateAddressFromLookup(Set $set, ?string $input, string $statePath): void
    {
        $query = trim((string) $input);

        if ($query === '' || strlen($query) < 4) {
            return;
        }

        try {
            $address = app(PlacesAutocompleteService::class)->resolveAddress($query);
        } catch (Throwable $exception) {
            report($exception);

            return;
        }

        if ($address === null) {
            return;
        }

        $fields = [
            'street' => $address['street'] ?? null,
            'line2' => $address['line2'] ?? null,
            'city' => $address['city'] ?? null,
            'state' => $address['state'] ?? null,
            'zip' => $address['zip'] ?? null,
            'country' => $address['country'] ?? null,
            'formatted' => $address['formatted'] ?? null,
            'place_id' => $address['place_id'] ?? null,
            'latitude' => $address['latitude'] ?? null,
            'longitude' => $address['longitude'] ?? null,
        ];

        foreach ($fields as $field => $value) {
            $set($statePath . '.' . $field, $value);
        }
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->url(fn(Customer $record) => CustomerResource::getUrl('edit', ['record' => $record])),

                TextColumn::make('company')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('opportunities_count')
                    ->counts('opportunities')
                    ->label('Opportunities')
                    ->badge()
                    ->color('success'),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->since(),
            ])
            ->filters([
                // Filters would be configured differently in this version
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\OpportunitiesRelationManager::class, // TODO: Convert to Schema API
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
