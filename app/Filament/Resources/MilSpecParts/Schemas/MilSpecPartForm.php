<?php

namespace App\Filament\Resources\MilSpecParts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MilSpecPartForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Part Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nsn')
                            ->label('National Stock Number (NSN)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('XXXX-XX-XXX-XXXX')
                            ->helperText('Format: XXXX-XX-XXX-XXXX (13 digits with dashes)'),
                        TextInput::make('manufacturer_part_number')
                            ->label('Manufacturer Part Number')
                            ->maxLength(255)
                            ->nullable(),
                        Textarea::make('description')
                            ->required()
                            ->maxLength(65535)
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Manufacturer')
                    ->schema([
                        Select::make('manufacturer_id')
                            ->label('Manufacturer')
                            ->relationship('manufacturer', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('cage_code')
                                    ->label('CAGE Code')
                                    ->maxLength(5)
                                    ->nullable(),
                            ])
                            ->nullable(),
                    ]),
            ]);
    }
}
