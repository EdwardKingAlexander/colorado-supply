<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorResource\Pages\CreateVendor;
use App\Filament\Resources\VendorResource\Pages\EditVendor;
use App\Filament\Resources\VendorResource\Pages\ListVendors;
use App\Models\Vendor;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vendor Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Vendor Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('General Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('General Phone')
                            ->tel()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('Address')
                            ->rows(3)
                            ->maxLength(1000),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Contacts')
                    ->description('Optional personal contacts for this vendor. Mark one contact as preferred when applicable.')
                    ->schema([
                        Repeater::make('contacts')
                            ->relationship('contacts')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Contact Name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('job_title')
                                    ->label('Job Title')
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),
                                TextInput::make('phone')
                                    ->label('Work Phone')
                                    ->tel()
                                    ->maxLength(50),
                                TextInput::make('mobile_phone')
                                    ->label('Mobile Phone')
                                    ->tel()
                                    ->maxLength(50),
                                Toggle::make('is_preferred')
                                    ->label('Preferred Contact')
                                    ->helperText('Selecting this contact clears the previous preferred contact.'),
                                Textarea::make('notes')
                                    ->rows(2)
                                    ->maxLength(1000)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Add Contact')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'New Contact')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Vendor Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->url(fn ($record) => filled($record->email) ? "mailto:{$record->email}" : null)
                    ->searchable()
                    ->placeholder('--')
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->url(fn ($record) => filled($record->phone) ? "tel:{$record->phone}" : null)
                    ->badge()
                    ->color('primary')
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->placeholder('--')
                    ->sortable(),
                TextColumn::make('preferredContact.name')
                    ->label('Preferred Contact')
                    ->placeholder('--')
                    ->searchable(),
                TextColumn::make('preferredContact.email')
                    ->label('Contact Email')
                    ->url(fn ($record) => filled($record->preferredContact?->email) ? "mailto:{$record->preferredContact->email}" : null)
                    ->placeholder('--'),
                TextColumn::make('preferred_contact_phone')
                    ->label('Contact Phone')
                    ->getStateUsing(fn (Vendor $record) => $record->preferredContact?->phone ?: $record->preferredContact?->mobile_phone)
                    ->url(fn (Vendor $record) => filled($record->preferredContact?->phone ?: $record->preferredContact?->mobile_phone)
                        ? 'tel:'.($record->preferredContact?->phone ?: $record->preferredContact?->mobile_phone)
                        : null)
                    ->fontFamily(FontFamily::Mono)
                    ->placeholder('--'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendors::route('/'),
            'create' => CreateVendor::route('/create'),
            'edit' => EditVendor::route('/{record}/edit'),
        ];
    }
}
