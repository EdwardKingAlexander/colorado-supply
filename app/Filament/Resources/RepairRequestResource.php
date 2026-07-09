<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RepairRequestResource\Pages;
use App\Filament\Resources\RepairRequestResource\Pages\ListRepairRequests;
use App\Filament\Resources\RepairRequestResource\Pages\ViewRepairRequest;
use App\Models\RepairRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RepairRequestResource extends Resource
{
    protected static ?string $model = RepairRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'CRM';

    protected static ?string $navigationLabel = 'Repair Requests';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        // For View/Edit pages (we’ll keep fields read-only by default)
        return $schema->components([
            TextInput::make('name')->disabled()->dehydrated(false),
            TextInput::make('email')->disabled()->dehydrated(false),
            TextInput::make('phone')->disabled()->dehydrated(false),
            TextInput::make('company')->disabled()->dehydrated(false),
            TextInput::make('equipment_type')->disabled()->dehydrated(false),
            TextInput::make('manufacturer')->disabled()->dehydrated(false),
            TextInput::make('model_number')->disabled()->dehydrated(false),
            TextInput::make('serial_number')->disabled()->dehydrated(false),
            TextInput::make('urgency')->disabled()->dehydrated(false),
            Textarea::make('issue_description')->rows(8)->disabled()->dehydrated(false),
            TextInput::make('ip')->label('IP')->disabled()->dehydrated(false),
            TextInput::make('user_agent')->label('User Agent')->disabled()->dehydrated(false),
            DateTimePicker::make('handled_at')->label('Handled At'),
            Select::make('handled_by')
                ->relationship('handledBy', 'name')
                ->searchable()
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_handled')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->getStateUsing(fn (RepairRequest $record) => $record->is_handled)
                    ->sortable(),

                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('phone')->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('equipment_type')->label('Equipment')->searchable()->sortable(),
                TextColumn::make('manufacturer')->toggleable(isToggledHiddenByDefault: true)->searchable(),
                TextColumn::make('model_number')->label('Model #')->searchable(),
                TextColumn::make('serial_number')->label('Serial #')->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('urgency')
                    ->badge()
                    ->color(fn (?string $state) => $state === 'rush' ? 'danger' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('issue_description')
                    ->label('Issue (preview)')
                    ->limit(80)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('handled')
                    ->label('Handled')
                    ->placeholder('All')
                    ->trueLabel('Handled')
                    ->falseLabel('Unhandled')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('handled_at'),
                        false: fn (Builder $q) => $q->whereNull('handled_at'),
                        blank: fn (Builder $q) => $q
                    ),

                Filter::make('received_at')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        return $q
                            ->when($data['from'] ?? null, fn ($qq, $d) => $qq->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($qq, $d) => $qq->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('mark_handled')
                    ->label('Mark handled')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (RepairRequest $record) => is_null($record->handled_at))
                    ->requiresConfirmation()
                    ->action(function (RepairRequest $record) {
                        $record->handled_at = now();
                        $record->handled_by = auth()->id();
                        $record->save();
                    }),

                Action::make('mark_unhandled')
                    ->label('Mark unhandled')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (RepairRequest $record) => ! is_null($record->handled_at))
                    ->requiresConfirmation()
                    ->action(function (RepairRequest $record) {
                        $record->handled_at = null;
                        $record->handled_by = null;
                        $record->save();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRepairRequests::route('/'),
            'view' => ViewRepairRequest::route('/{record}'),
            // no create/edit pages for external submissions
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'equipment_type', 'model_number', 'manufacturer'];
    }
}
