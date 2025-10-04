<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ContactMessageResource\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessageResource\Pages\ViewContactMessage;
use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-inbox';
    protected static string | \UnitEnum | null $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Contact Messages';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        // For View/Edit pages (we’ll keep fields read-only by default)
        return $schema->components([
            TextInput::make('name')->disabled()->dehydrated(false),
            TextInput::make('email')->disabled()->dehydrated(false),
            TextInput::make('phone')->disabled()->dehydrated(false),
            Textarea::make('message')->rows(8)->disabled()->dehydrated(false),
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
                    ->getStateUsing(fn (ContactMessage $record) => $record->is_handled)
                    ->sortable(),

                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('phone')->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('message')
                    ->label('Message (preview)')
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
                    ->visible(fn (ContactMessage $record) => is_null($record->handled_at))
                    ->requiresConfirmation()
                    ->action(function (ContactMessage $record) {
                        $record->handled_at = now();
                        $record->handled_by = auth()->id();
                        $record->save();
                    }),

                Action::make('mark_unhandled')
                    ->label('Mark unhandled')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (ContactMessage $record) => ! is_null($record->handled_at))
                    ->requiresConfirmation()
                    ->action(function (ContactMessage $record) {
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
            'index' => ListContactMessages::route('/'),
            'view'  => ViewContactMessage::route('/{record}'),
            // no create/edit pages for external submissions
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone', 'message'];
    }
}
