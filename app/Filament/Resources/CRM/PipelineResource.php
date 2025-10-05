<?php

namespace App\Filament\Resources\CRM;

use App\Filament\Resources\CRM\PipelineResource\Pages;
use App\Filament\Resources\CRM\PipelineResource\RelationManagers;
use App\Models\Pipeline;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;

class PipelineResource extends Resource
{
    protected static ?string $model = Pipeline::class;

    protected static string | \UnitEnum | null $navigationGroup = 'CRM';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pipeline Details')->columns(2)->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),

                Toggle::make('is_default')
                    ->label('Default Pipeline')
                    ->helperText('Mark this as the default pipeline for new opportunities'),

                TextInput::make('position')
                    ->numeric()
                    ->default(0)
                    ->helperText('Used for sorting pipelines'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('stages_count')
                    ->counts('stages')
                    ->label('Stages')
                    ->badge()
                    ->color('info'),

                TextColumn::make('opportunities_count')
                    ->counts('opportunities')
                    ->label('Opportunities')
                    ->badge()
                    ->color('success'),

                TextColumn::make('position')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->since(),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('position')
            ->reorderable('position');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\StagesRelationManager::class, // TODO: Convert to Schema API
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPipelines::route('/'),
            'create' => Pages\CreatePipeline::route('/create'),
            'edit' => Pages\EditPipeline::route('/{record}/edit'),
        ];
    }
}
