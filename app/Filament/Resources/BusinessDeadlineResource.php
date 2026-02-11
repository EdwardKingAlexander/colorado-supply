<?php

namespace App\Filament\Resources;

use App\Enums\DeadlineCategory;
use App\Enums\RecurrenceType;
use App\Filament\Resources\BusinessDeadlineResource\Pages;
use App\Models\BusinessDeadline;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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

class BusinessDeadlineResource extends Resource
{
    protected static ?string $model = BusinessDeadline::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Business Hub';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Deadline Information')->columns(2)->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->rows(2)
                    ->maxLength(1000)
                    ->columnSpanFull(),

                Select::make('category')
                    ->options(collect(DeadlineCategory::cases())->mapWithKeys(fn ($cat) => [$cat->value => $cat->label()]))
                    ->required()
                    ->native(false),

                DatePicker::make('due_date')
                    ->required()
                    ->native(false),
            ]),

            Section::make('Recurrence')->columns(2)->schema([
                Select::make('recurrence')
                    ->options(collect(RecurrenceType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()]))
                    ->default(RecurrenceType::Once->value)
                    ->required()
                    ->native(false)
                    ->live(),

                TextInput::make('external_url')
                    ->label('Filing URL')
                    ->url()
                    ->maxLength(500)
                    ->placeholder('https://...'),
            ]),

            Section::make('Reminders')->schema([
                TagsInput::make('reminder_days')
                    ->label('Send reminders (days before due date)')
                    ->placeholder('Add days...')
                    ->default([30, 14, 7, 1])
                    ->helperText('Enter the number of days before the due date to send reminders'),
            ]),

            Section::make('Related Document')->schema([
                Select::make('related_document_id')
                    ->label('Related Document')
                    ->relationship('relatedDocument', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Select a document (optional)'),
            ])->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Mobile-first layout
                Stack::make([
                    // Header: Icon + Title + Completion status
                    Split::make([
                        IconColumn::make('completion_icon')
                            ->label('')
                            ->state(fn (?BusinessDeadline $record) => $record?->isCompleted())
                            ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-clock')
                            ->color(fn ($state) => $state ? 'success' : 'warning')
                            ->size('xl')
                            ->grow(false),

                        TextColumn::make('title')
                            ->searchable()
                            ->sortable()
                            ->weight(FontWeight::Bold)
                            ->size('base')
                            ->color(fn (?BusinessDeadline $record) => $record?->isCompleted() ? 'gray' : 'primary')
                            ->formatStateUsing(fn ($state, ?BusinessDeadline $record) => $record?->isCompleted() ? '<s>'.$state.'</s>' : $state)
                            ->html()
                            ->grow(),
                    ])
                        ->grow(false),

                    // Row 2: Category + Due Date
                    Split::make([
                        TextColumn::make('category')
                            ->badge()
                            ->formatStateUsing(fn (?DeadlineCategory $state) => $state?->label() ?? 'Unknown')
                            ->color(fn (?DeadlineCategory $state) => match ($state) {
                                DeadlineCategory::Tax => 'danger',
                                DeadlineCategory::LicenseRenewal => 'warning',
                                DeadlineCategory::Registration => 'primary',
                                DeadlineCategory::Compliance => 'success',
                                DeadlineCategory::Other => 'gray',
                                null => 'gray',
                            })
                            ->size('xs'),

                        TextColumn::make('due_date')
                            ->date('M j')
                            ->sortable()
                            ->size('xs')
                            ->color(fn (?BusinessDeadline $record) => match (true) {
                                $record?->isCompleted() => 'gray',
                                $record?->isOverdue() => 'danger',
                                $record?->isDueSoon() => 'warning',
                                default => 'success',
                            })
                            ->weight(FontWeight::Medium)
                            ->alignment(Alignment::End),
                    ])
                        ->grow(false),

                    // Row 3: Days remaining (prominent)
                    TextColumn::make('time_status')
                        ->label('')
                        ->state(fn (?BusinessDeadline $record) => $record)
                        ->formatStateUsing(function (?BusinessDeadline $record) {
                            if ($record?->isCompleted()) {
                                return 'Completed';
                            }
                            if ($record?->isOverdue()) {
                                return abs($record->daysUntilDue()).' days overdue';
                            }

                            return $record->daysUntilDue().' days left';
                        })
                        ->color(fn (?BusinessDeadline $record) => match (true) {
                            $record?->isCompleted() => 'success',
                            $record?->isOverdue() => 'danger',
                            $record?->isDueSoon() => 'warning',
                            default => 'gray',
                        })
                        ->size('sm')
                        ->weight(FontWeight::Medium)
                        ->icon(fn (?BusinessDeadline $record) => match (true) {
                            $record?->isCompleted() => 'heroicon-m-check-badge',
                            $record?->isOverdue() => 'heroicon-m-exclamation-circle',
                            $record?->isDueSoon() => 'heroicon-m-exclamation-triangle',
                            default => 'heroicon-m-clock',
                        }),

                    // Description (if exists)
                    TextColumn::make('description')
                        ->color('gray')
                        ->size('sm')
                        ->limit(50)
                        ->placeholder('No description')
                        ->visible(fn ($state) => filled($state)),
                ])
                    ->space(2),

                // Desktop layout (hidden on mobile)
                Split::make([
                    Stack::make([
                        Split::make([
                            IconColumn::make('completion_icon_desktop')
                                ->label('')
                                ->state(fn (?BusinessDeadline $record) => $record?->isCompleted() ? 'completed' : 'pending')
                                ->icon(fn ($state) => $state === 'completed' ? 'heroicon-o-check-circle' : 'heroicon-o-clock')
                                ->color(fn ($state) => $state === 'completed' ? 'success' : 'warning')
                                ->size('lg')
                                ->grow(false),

                            TextColumn::make('title_desktop')
                                ->searchable()
                                ->sortable()
                                ->weight(FontWeight::Bold)
                                ->size('lg')
                                ->color(fn (?BusinessDeadline $record) => $record?->isCompleted() ? 'gray' : 'primary')
                                ->formatStateUsing(fn ($state, ?BusinessDeadline $record) => $record?->isCompleted() ? '<s>'.$state.'</s>' : $state)
                                ->html()
                                ->grow(),
                        ])
                            ->grow(false),

                        TextColumn::make('category_desktop')
                            ->badge()
                            ->formatStateUsing(fn (?DeadlineCategory $state) => $state?->label() ?? 'Unknown')
                            ->color(fn (?DeadlineCategory $state) => match ($state) {
                                DeadlineCategory::Tax => 'danger',
                                DeadlineCategory::LicenseRenewal => 'warning',
                                DeadlineCategory::Registration => 'primary',
                                DeadlineCategory::Compliance => 'success',
                                DeadlineCategory::Other => 'gray',
                                null => 'gray',
                            })
                            ->size('sm'),

                        TextColumn::make('description_desktop')
                            ->color('gray')
                            ->size('sm')
                            ->limit(60)
                            ->placeholder('No description')
                            ->visible(fn ($state) => filled($state)),
                    ])
                        ->space(2)
                        ->grow(),

                    Stack::make([
                        TextColumn::make('due_date_desktop')
                            ->date('M j, Y')
                            ->sortable()
                            ->alignment(Alignment::End)
                            ->size('base')
                            ->weight(FontWeight::Medium)
                            ->color(fn (?BusinessDeadline $record) => match (true) {
                                $record?->isCompleted() => 'gray',
                                $record?->isOverdue() => 'danger',
                                $record?->isDueSoon() => 'warning',
                                default => 'primary',
                            })
                            ->icon(fn (?BusinessDeadline $record) => $record?->isCompleted() ? 'heroicon-m-check' : 'heroicon-m-calendar')
                            ->grow(false),

                        TextColumn::make('urgency_status_desktop')
                            ->label('')
                            ->state(fn (?BusinessDeadline $record) => $record)
                            ->formatStateUsing(function (?BusinessDeadline $record) {
                                if ($record?->isCompleted()) {
                                    return 'Completed';
                                }
                                if ($record?->isOverdue()) {
                                    return abs($record->daysUntilDue()).' days overdue';
                                }

                                return $record->daysUntilDue().' days remaining';
                            })
                            ->color(fn (?BusinessDeadline $record) => match (true) {
                                $record?->isCompleted() => 'success',
                                $record?->isOverdue() => 'danger',
                                $record?->isDueSoon() => 'warning',
                                default => 'gray',
                            })
                            ->size('sm')
                            ->alignment(Alignment::End)
                            ->grow(false),

                        TextColumn::make('time_progress_desktop')
                            ->label('')
                            ->state(fn (?BusinessDeadline $record) => $record)
                            ->formatStateUsing(function (?BusinessDeadline $record) {
                                if ($record?->isCompleted()) {
                                    return 'Done';
                                }
                                if ($record?->isOverdue()) {
                                    return 'Overdue';
                                }
                                $days = $record->daysUntilDue();
                                if ($days <= 7) {
                                    return 'Critical';
                                }
                                if ($days <= 14) {
                                    return 'Urgent';
                                }

                                return 'On Track';
                            })
                            ->color(fn (?BusinessDeadline $record) => match (true) {
                                $record?->isCompleted() => 'success',
                                $record?->isOverdue() => 'danger',
                                $record?->isDueSoon() => 'warning',
                                default => 'primary',
                            })
                            ->size('xs')
                            ->weight(FontWeight::Bold)
                            ->alignment(Alignment::End)
                            ->grow(false)
                            ->badge(),
                    ])
                        ->alignment(Alignment::End)
                        ->space(1),
                ])
                    ->from('md')
                    ->hiddenFrom('md'),

                // Collapsible details panel
                Panel::make([
                    Grid::make([
                        'default' => 1,
                        'sm' => 2,
                    ])
                        ->schema([
                            Stack::make([
                                TextColumn::make('relatedDocument.name')
                                    ->label('Linked Document')
                                    ->icon('heroicon-m-document-text')
                                    ->placeholder('No linked document')
                                    ->size('sm')
                                    ->color('gray'),

                                TextColumn::make('reminder_days')
                                    ->label('Reminders')
                                    ->formatStateUsing(function ($state) {
                                        if (! $state) {
                                            return 'No reminders';
                                        }
                                        if (is_array($state)) {
                                            return implode(', ', $state).' days before';
                                        }

                                        return $state.' days before';
                                    })
                                    ->icon('heroicon-m-bell-alert')
                                    ->size('sm')
                                    ->color('gray'),

                                TextColumn::make('completed_at')
                                    ->label('Completed On')
                                    ->dateTime('M j, Y')
                                    ->icon('heroicon-m-check-circle')
                                    ->size('sm')
                                    ->color('success')
                                    ->visible(fn ($state) => $state !== null),
                            ])->space(2),

                            Stack::make([
                                TextColumn::make('external_url')
                                    ->label('Filing Portal')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->url(fn ($record) => $record->external_url)
                                    ->openUrlInNewTab()
                                    ->formatStateUsing(fn ($state) => $state ? 'Access portal' : null)
                                    ->placeholder('No portal link')
                                    ->size('sm')
                                    ->color(fn ($state) => $state ? 'primary' : 'gray'),

                                TextColumn::make('created_at')
                                    ->label('Created')
                                    ->date('M j, Y')
                                    ->icon('heroicon-m-clock')
                                    ->size('sm')
                                    ->color('gray'),

                                TextColumn::make('description_full')
                                    ->label('Full Description')
                                    ->state(fn (?BusinessDeadline $record) => $record?->description)
                                    ->limit(150)
                                    ->placeholder('No additional details')
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
            ->defaultSort('due_date', 'asc')
            ->filters([
                SelectFilter::make('category')
                    ->options(collect(DeadlineCategory::cases())->mapWithKeys(fn ($cat) => [$cat->value => $cat->label()])),

                SelectFilter::make('recurrence')
                    ->options(collect(RecurrenceType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()])),

                TernaryFilter::make('completed')
                    ->label('Completion Status')
                    ->placeholder('All')
                    ->trueLabel('Completed')
                    ->falseLabel('Pending')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('completed_at'),
                        false: fn ($query) => $query->whereNull('completed_at'),
                    ),
            ])
            ->recordActions([
                Action::make('complete')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Completed')
                    ->modalDescription('Are you sure you want to mark this deadline as completed?')
                    ->hidden(fn (?BusinessDeadline $record) => $record?->isCompleted())
                    ->action(function (?BusinessDeadline $record) {
                        $record?->markAsCompleted();

                        if ($record->recurrence !== RecurrenceType::Once) {
                            $next = $record->createNextRecurrence();
                            if ($next) {
                                Notification::make()
                                    ->title('Next deadline created')
                                    ->body('Due: '.$next->due_date->format('M j, Y'))
                                    ->success()
                                    ->send();
                            }
                        }
                    }),

                Action::make('reopen')
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->hidden(fn (?BusinessDeadline $record) => ! $record?->isCompleted())
                    ->action(fn (?BusinessDeadline $record) => $record?->markAsIncomplete()),

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
            'index' => Pages\ListBusinessDeadlines::route('/'),
            'create' => Pages\CreateBusinessDeadline::route('/create'),
            'edit' => Pages\EditBusinessDeadline::route('/{record}/edit'),
        ];
    }
}
