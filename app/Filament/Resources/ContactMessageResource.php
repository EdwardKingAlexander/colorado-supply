<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Contact Messages';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        // For View/Edit pages (we’ll keep fields read-only by default)
        return $form->schema([
            Forms\Components\TextInput::make('name')->disabled()->dehydrated(false),
            Forms\Components\TextInput::make('email')->disabled()->dehydrated(false),
            Forms\Components\TextInput::make('phone')->disabled()->dehydrated(false),
            Forms\Components\Textarea::make('message')->rows(8)->disabled()->dehydrated(false),
            Forms\Components\TextInput::make('ip')->label('IP')->disabled()->dehydrated(false),
            Forms\Components\TextInput::make('user_agent')->label('User Agent')->disabled()->dehydrated(false),
            Forms\Components\DateTimePicker::make('handled_at')->label('Handled At'),
            Forms\Components\Select::make('handled_by')
                ->relationship('handledBy', 'name')
                ->searchable()
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_handled')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->getStateUsing(fn (ContactMessage $record) => $record->is_handled)
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('message')
                    ->label('Message (preview)')
                    ->limit(80)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('handled')
                    ->label('Handled')
                    ->placeholder('All')
                    ->trueLabel('Handled')
                    ->falseLabel('Unhandled')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('handled_at'),
                        false: fn (Builder $q) => $q->whereNull('handled_at'),
                        blank: fn (Builder $q) => $q
                    ),

                Tables\Filters\Filter::make('received_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From'),
                        Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        return $q
                            ->when($data['from'] ?? null, fn ($qq, $d) => $qq->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($qq, $d) => $qq->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('mark_handled')
                    ->label('Mark handled')
                    ->icon('heroicon-o-check')
                    ->visible(fn (ContactMessage $record) => is_null($record->handled_at))
                    ->requiresConfirmation()
                    ->action(function (ContactMessage $record) {
                        $record->handled_at = now();
                        $record->handled_by = auth()->id();
                        $record->save();
                    }),

                Tables\Actions\Action::make('mark_unhandled')
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
            'view'  => Pages\ViewContactMessage::route('/{record}'),
            // no create/edit pages for external submissions
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone', 'message'];
    }
}
