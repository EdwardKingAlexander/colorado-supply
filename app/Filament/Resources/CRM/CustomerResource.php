<?php

namespace App\Filament\Resources\CRM;

use App\Filament\Resources\CRM\CustomerResource\Pages;
use App\Filament\Resources\CRM\CustomerResource\RelationManagers;
use App\Models\Customer;
use App\Models\User;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

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

            Section::make('Addresses')->columns(2)->schema([
                KeyValue::make('billing_address')
                    ->label('Billing Address')
                    ->keyLabel('Field')
                    ->valueLabel('Value')
                    ->addActionLabel('Add field')
                    ->default([
                        'street' => '',
                        'city' => '',
                        'state' => '',
                        'zip' => '',
                        'country' => '',
                    ]),

                KeyValue::make('shipping_address')
                    ->label('Shipping Address')
                    ->keyLabel('Field')
                    ->valueLabel('Value')
                    ->addActionLabel('Add field')
                    ->default([
                        'street' => '',
                        'city' => '',
                        'state' => '',
                        'zip' => '',
                        'country' => '',
                    ]),
            ])->collapsible(),
        ]);
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
