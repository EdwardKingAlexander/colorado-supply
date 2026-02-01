<?php

namespace App\Filament\Resources;

use App\Enums\LinkCategory;
use App\Filament\Resources\BusinessLinkResource\Pages;
use App\Models\BusinessLink;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BusinessLinkResource extends Resource
{
    protected static ?string $model = BusinessLink::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static string|\UnitEnum|null $navigationGroup = 'Business Hub';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Link Information')->columns(2)->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Select::make('category')
                    ->options(collect(LinkCategory::cases())->mapWithKeys(fn ($cat) => [$cat->value => $cat->label()]))
                    ->required()
                    ->native(false),

                TextInput::make('url')
                    ->required()
                    ->url()
                    ->maxLength(500)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]),

            Section::make('Display Options')->columns(3)->schema([
                TextInput::make('icon')
                    ->placeholder('heroicon-o-globe-alt')
                    ->helperText('Heroicon name (e.g., heroicon-o-building-library)')
                    ->maxLength(100),

                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]),
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
                            ->description(fn (BusinessLink $record) => $record->description ? \Str::limit($record->description, 60) : null),

                        TextColumn::make('category')
                            ->badge()
                            ->formatStateUsing(fn (LinkCategory $state) => $state->label())
                            ->color(fn (LinkCategory $state) => $state->color())
                            ->sortable(),
                    ])->grow(),

                    Stack::make([
                        IconColumn::make('is_active')
                            ->label('Active')
                            ->boolean()
                            ->sortable(),

                        TextColumn::make('sort_order')
                            ->label('Order')
                            ->sortable()
                            ->toggleable(),
                    ]),
                ])->from('md'),

                Panel::make([
                    TextColumn::make('url')
                        ->label('URL')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn ($record) => $record->url)
                        ->openUrlInNewTab()
                        ->limit(40)
                        ->tooltip(fn ($record) => $record->url),

                    TextColumn::make('icon')
                        ->toggleable(isToggledHiddenByDefault: true),
                ])->collapsible()->collapsed(),
            ])
            ->contentGrid(['md' => 2])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->filters([
                SelectFilter::make('category')
                    ->options(collect(LinkCategory::cases())->mapWithKeys(fn ($cat) => [$cat->value => $cat->label()])),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
            ])
            ->recordActions([
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
            'index' => Pages\ListBusinessLinks::route('/'),
            'create' => Pages\CreateBusinessLink::route('/create'),
            'edit' => Pages\EditBusinessLink::route('/{record}/edit'),
        ];
    }
}
