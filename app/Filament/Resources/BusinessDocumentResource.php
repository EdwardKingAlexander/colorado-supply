<?php

namespace App\Filament\Resources;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Filament\Resources\BusinessDocumentResource\Pages;
use App\Models\BusinessDocument;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BusinessDocumentResource extends Resource
{
    protected static ?string $model = BusinessDocument::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Business Hub';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Document Information')->columns(2)->schema([
                Select::make('type')
                    ->options(collect(DocumentType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()]))
                    ->required()
                    ->native(false),

                Select::make('status')
                    ->options(collect(DocumentStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()]))
                    ->default(DocumentStatus::Active->value)
                    ->required()
                    ->native(false),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->rows(2)
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]),

            Section::make('Document Details')->columns(2)->schema([
                TextInput::make('document_number')
                    ->label('Document/License Number')
                    ->maxLength(100),

                TextInput::make('issuing_authority')
                    ->maxLength(255),

                DatePicker::make('issue_date')
                    ->native(false),

                DatePicker::make('expiration_date')
                    ->native(false),
            ]),

            Section::make('File Upload')->schema([
                FileUpload::make('file_path')
                    ->label('Document File')
                    ->directory('business-documents')
                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                    ->maxSize(10240)
                    ->downloadable()
                    ->openable()
                    ->previewable(),
            ]),

            Section::make('Additional Information')->schema([
                KeyValue::make('metadata')
                    ->label('Custom Fields')
                    ->addActionLabel('Add Field')
                    ->keyLabel('Field Name')
                    ->valueLabel('Value'),
            ])->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (?BusinessDocument $record) => $record?->document_number ? '#'.$record->document_number : null),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?DocumentType $state) => $state?->label() ?? 'Unknown')
                    ->color(fn (?DocumentType $state) => match ($state) {
                        DocumentType::License => 'info',
                        DocumentType::Insurance => 'success',
                        DocumentType::Registration => 'primary',
                        DocumentType::TaxDocument => 'warning',
                        DocumentType::Contract => 'gray',
                        DocumentType::Other => 'gray',
                        null => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (DocumentStatus $state) => $state->label())
                    ->color(fn (DocumentStatus $state) => match ($state) {
                        DocumentStatus::Active => 'success',
                        DocumentStatus::Expired => 'danger',
                        DocumentStatus::PendingRenewal => 'warning',
                        DocumentStatus::Archived => 'gray',
                    }),

                TextColumn::make('issuing_authority')
                    ->label('Issuer')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('expiration_date')
                    ->label('Expires')
                    ->date('M j, Y')
                    ->sortable()
                    ->placeholder('No expiration')
                    ->color(fn (?BusinessDocument $record) => match (true) {
                        $record?->isExpired() => 'danger',
                        $record?->isExpiringSoon() => 'warning',
                        default => null,
                    })
                    ->description(fn (?BusinessDocument $record) => match (true) {
                        ! $record?->expiration_date => null,
                        $record?->isExpired() => abs($record->daysUntilExpiration()).'d overdue',
                        $record?->isExpiringSoon() => $record->daysUntilExpiration().'d left',
                        default => null,
                    }),
            ])
            ->defaultSort('expiration_date', 'asc')
            ->filters([
                SelectFilter::make('type')
                    ->options(collect(DocumentType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()])),

                SelectFilter::make('status')
                    ->options(collect(DocumentStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-m-eye')
                    ->size('sm'),
                EditAction::make()
                    ->icon('heroicon-m-pencil-square')
                    ->size('sm'),
                DeleteAction::make()
                    ->icon('heroicon-m-trash')
                    ->size('sm'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessDocuments::route('/'),
            'create' => Pages\CreateBusinessDocument::route('/create'),
            'view' => Pages\ViewBusinessDocument::route('/{record}'),
            'edit' => Pages\EditBusinessDocument::route('/{record}/edit'),
        ];
    }
}
