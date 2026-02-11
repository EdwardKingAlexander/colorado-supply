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
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Grid;
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
                // Main card with futuristic styling
                Split::make([
                    // Left side: Link identity
                    Stack::make([
                        // Icon and name header - simplified for mobile
                        Split::make([
                            // Dynamic icon based on category or custom
                            IconColumn::make('link_icon')
                                ->label('')
                                ->state(fn (?BusinessLink $record) => $record)
                                ->icon(fn (?BusinessLink $record) => $record?->icon ?: match ($record?->category) {
                                    LinkCategory::Federal => 'heroicon-m-building-library',
                                    LinkCategory::State => 'heroicon-m-building-office-2',
                                    LinkCategory::Local => 'heroicon-m-map-pin',
                                    LinkCategory::Banking => 'heroicon-m-credit-card',
                                    LinkCategory::Vendor => 'heroicon-m-truck',
                                    LinkCategory::Other => 'heroicon-m-globe-alt',
                                })
                                ->color(fn (?BusinessLink $record) => $record?->is_active ? 'primary' : 'gray')
                                ->size('lg')
                                ->grow(false),

                            Stack::make([
                                TextColumn::make('name')
                                    ->searchable()
                                    ->sortable()
                                    ->weight(FontWeight::Bold)
                                    ->size('base')
                                    ->color(fn (?BusinessLink $record) => $record?->is_active ? 'primary' : 'gray')
                                    ->grow(),

                                // URL preview (truncated) - moved up for mobile
                                TextColumn::make('url_preview')
                                    ->label('')
                                    ->state(fn (?BusinessLink $record) => $record?->url)
                                    ->limit(30)
                                    ->color('gray')
                                    ->size('xs')
                                    ->icon('heroicon-m-link')
                                    ->grow(false),
                            ])->grow(),
                        ])
                            ->grow(false)
                            ->from('md'),

                        // Description - single line on mobile
                        TextColumn::make('description')
                            ->color('gray')
                            ->size('sm')
                            ->limit(50)
                            ->placeholder('No description')
                            ->visible(fn ($state) => filled($state)),

                        // Mobile: Single row with category and status
                        Split::make([
                            TextColumn::make('category')
                                ->badge()
                                ->formatStateUsing(fn (?LinkCategory $state) => $state?->label() ?? 'Unknown')
                                ->color(fn (?LinkCategory $state) => match ($state) {
                                    LinkCategory::Federal => 'primary',
                                    LinkCategory::State => 'primary',
                                    LinkCategory::Local => 'success',
                                    LinkCategory::Banking => 'gray',
                                    LinkCategory::Vendor => 'warning',
                                    LinkCategory::Other => 'gray',
                                    null => 'gray',
                                })
                                ->size('xs')
                                ->icon(fn (?LinkCategory $state) => match ($state) {
                                    LinkCategory::Federal => 'heroicon-m-building-library',
                                    LinkCategory::State => 'heroicon-m-building-office-2',
                                    LinkCategory::Local => 'heroicon-m-map-pin',
                                    LinkCategory::Banking => 'heroicon-m-credit-card',
                                    LinkCategory::Vendor => 'heroicon-m-truck',
                                    LinkCategory::Other => 'heroicon-m-link',
                                    null => 'heroicon-m-question-mark-circle',
                                }),

                            TextColumn::make('is_active')
                                ->label('')
                                ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                                ->badge()
                                ->color(fn ($state) => $state ? 'success' : 'danger')
                                ->size('xs')
                                ->alignment(Alignment::End)
                                ->grow(),
                        ])
                            ->grow(false),

                        // Mobile launch button
                        TextColumn::make('quick_launch')
                            ->label('')
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->url(fn ($record) => $record->url)
                            ->openUrlInNewTab()
                            ->formatStateUsing(fn () => 'Open Link')
                            ->color('primary')
                            ->size('sm')
                            ->weight(FontWeight::Medium)
                            ->alignment(Alignment::Start)
                            ->grow(false)
                            ->hiddenFrom('md'),
                    ])
                        ->space(2)
                        ->grow(),

                    // Right side: Status and actions - desktop only
                    Stack::make([
                        // Active status
                        TextColumn::make('is_active')
                            ->label('')
                            ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger')
                            ->size('sm')
                            ->icon(fn ($state) => $state ? 'heroicon-m-signal' : 'heroicon-m-signal-slash')
                            ->alignment(Alignment::End)
                            ->grow(false),

                        // Launch button (desktop)
                        TextColumn::make('launch_action')
                            ->label('')
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->url(fn ($record) => $record->url)
                            ->openUrlInNewTab()
                            ->formatStateUsing(fn () => 'Open')
                            ->color('primary')
                            ->size('sm')
                            ->weight(FontWeight::Medium)
                            ->alignment(Alignment::End)
                            ->grow(false)
                            ->visible(fn (?BusinessLink $record) => $record?->is_active),
                    ])
                        ->alignment(Alignment::End)
                        ->space(1)
                        ->visibleFrom('md'),
                ])->from('md'),

                // Collapsible details panel - simplified
                Panel::make([
                    Grid::make([
                        'default' => 1,
                        'sm' => 2,
                    ])
                        ->schema([
                            // Left column
                            Stack::make([
                                TextColumn::make('url_full')
                                    ->label('Full URL')
                                    ->state(fn (?BusinessLink $record) => $record?->url)
                                    ->icon('heroicon-m-globe-alt')
                                    ->copyable()
                                    ->copyMessage('URL copied')
                                    ->limit(40)
                                    ->size('sm')
                                    ->color('gray'),

                                TextColumn::make('icon_custom')
                                    ->label('Custom Icon')
                                    ->state(fn (?BusinessLink $record) => $record?->icon ?: 'Default')
                                    ->icon('heroicon-m-swatch')
                                    ->size('sm')
                                    ->color('gray'),

                                TextColumn::make('sort_order_detail')
                                    ->label('Sort Order')
                                    ->state(fn (?BusinessLink $record) => $record?->sort_order)
                                    ->formatStateUsing(fn ($state) => 'Position #'.$state)
                                    ->icon('heroicon-m-queue-list')
                                    ->color('gray')
                                    ->size('sm'),
                            ])->space(2),

                            // Right column
                            Stack::make([
                                TextColumn::make('created_at')
                                    ->label('Added')
                                    ->date('M j, Y')
                                    ->icon('heroicon-m-clock')
                                    ->size('sm')
                                    ->color('gray'),

                                TextColumn::make('description_full')
                                    ->label('Full Description')
                                    ->state(fn (?BusinessLink $record) => $record?->description)
                                    ->limit(150)
                                    ->placeholder('No additional description')
                                    ->size('sm')
                                    ->color('gray'),
                            ])->space(2),
                        ]),
                ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes([
                        'class' => 'mt-3 border-t border-gray-200 dark:border-gray-700 pt-3',
                    ]),
            ])
            ->contentGrid([
                'default' => 1,
                'lg' => 2,
            ])
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
            'index' => Pages\ListBusinessLinks::route('/'),
            'create' => Pages\CreateBusinessLink::route('/create'),
            'edit' => Pages\EditBusinessLink::route('/{record}/edit'),
        ];
    }
}
