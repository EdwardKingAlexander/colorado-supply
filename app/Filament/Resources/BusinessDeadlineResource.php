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
use Filament\Tables\Columns\IconColumn;
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
                Split::make([
                    Stack::make([
                        TextColumn::make('title')
                            ->searchable()
                            ->sortable()
                            ->description(fn (BusinessDeadline $record) => $record->description ? \Str::limit($record->description, 50) : null),

                        TextColumn::make('category')
                            ->badge()
                            ->formatStateUsing(fn (DeadlineCategory $state) => $state->label())
                            ->color(fn (DeadlineCategory $state) => $state->color())
                            ->sortable(),

                        TextColumn::make('recurrence')
                            ->badge()
                            ->formatStateUsing(fn (RecurrenceType $state) => $state->label())
                            ->color(fn (RecurrenceType $state) => $state->color())
                            ->toggleable(),
                    ])->grow(),

                    Stack::make([
                        TextColumn::make('due_date')
                            ->date()
                            ->sortable()
                            ->description(fn (BusinessDeadline $record) => match (true) {
                                $record->isCompleted() => 'Completed',
                                $record->isOverdue() => 'Overdue by ' . abs($record->daysUntilDue()) . ' days',
                                default => $record->daysUntilDue() . ' days remaining',
                            })
                            ->color(fn (BusinessDeadline $record) => match (true) {
                                $record->isCompleted() => 'success',
                                $record->isOverdue() => 'danger',
                                $record->isDueSoon() => 'warning',
                                default => null,
                            }),

                        IconColumn::make('completed_at')
                            ->label('Status')
                            ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-clock')
                            ->color(fn ($state) => $state ? 'success' : 'warning'),
                    ]),
                ])->from('md'),

                Panel::make([
                    Split::make([
                        TextColumn::make('external_url')
                            ->label('Link')
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->url(fn ($record) => $record->external_url)
                            ->openUrlInNewTab()
                            ->formatStateUsing(fn ($state) => $state ? 'Open' : null)
                            ->toggleable(),

                        TextColumn::make('relatedDocument.name')
                            ->label('Document')
                            ->toggleable(isToggledHiddenByDefault: true),
                    ])->from('md'),
                ])->collapsible()->collapsed(),
            ])
            ->contentGrid(['md' => 2])
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
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Completed')
                    ->modalDescription('Are you sure you want to mark this deadline as completed?')
                    ->hidden(fn (BusinessDeadline $record) => $record->isCompleted())
                    ->action(function (BusinessDeadline $record) {
                        $record->markAsCompleted();

                        if ($record->recurrence !== RecurrenceType::Once) {
                            $next = $record->createNextRecurrence();
                            if ($next) {
                                Notification::make()
                                    ->title('Next deadline created')
                                    ->body('Due: ' . $next->due_date->format('M j, Y'))
                                    ->success()
                                    ->send();
                            }
                        }
                    }),

                Action::make('reopen')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->hidden(fn (BusinessDeadline $record) => ! $record->isCompleted())
                    ->action(fn (BusinessDeadline $record) => $record->markAsIncomplete()),

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
            'index' => Pages\ListBusinessDeadlines::route('/'),
            'create' => Pages\CreateBusinessDeadline::route('/create'),
            'edit' => Pages\EditBusinessDeadline::route('/{record}/edit'),
        ];
    }
}
