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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
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
                Split::make([
                    Stack::make([
                        TextColumn::make('name')
                            ->searchable()
                            ->sortable()
                            ->description(fn (BusinessDocument $record) => $record->document_number),

                        Split::make([
                            TextColumn::make('type')
                                ->badge()
                                ->formatStateUsing(fn (DocumentType $state) => $state->label())
                                ->color(fn (DocumentType $state) => $state->color())
                                ->sortable(),

                            TextColumn::make('status')
                                ->badge()
                                ->formatStateUsing(fn (DocumentStatus $state) => $state->label())
                                ->color(fn (DocumentStatus $state) => $state->color())
                                ->sortable(),
                        ])->from('md'),
                    ])->grow(),

                    Stack::make([
                        TextColumn::make('expiration_date')
                            ->date()
                            ->sortable()
                            ->description(fn (BusinessDocument $record) => $record->daysUntilExpiration() !== null
                                ? ($record->daysUntilExpiration() < 0
                                    ? 'Expired ' . abs($record->daysUntilExpiration()) . ' days ago'
                                    : $record->daysUntilExpiration() . ' days remaining')
                                : null
                            )
                            ->color(fn (BusinessDocument $record) => match (true) {
                                $record->isExpired() => 'danger',
                                $record->isExpiringSoon() => 'warning',
                                default => null,
                            }),

                        IconColumn::make('file_path')
                            ->label('File')
                            ->icon(fn ($state) => $state ? 'heroicon-o-document' : 'heroicon-o-x-mark')
                            ->color(fn ($state) => $state ? 'success' : 'gray')
                            ->toggleable(),
                    ]),
                ])->from('md'),

                Panel::make([
                    TextColumn::make('issuing_authority')
                        ->label('Issued by')
                        ->searchable()
                        ->toggleable(),

                    TextColumn::make('updated_at')
                        ->dateTime('M j, Y')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])->collapsible()->collapsed(),
            ])
            ->contentGrid(['md' => 2])
            ->defaultSort('expiration_date', 'asc')
            ->filters([
                SelectFilter::make('type')
                    ->options(collect(DocumentType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()])),

                SelectFilter::make('status')
                    ->options(collect(DocumentStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
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
