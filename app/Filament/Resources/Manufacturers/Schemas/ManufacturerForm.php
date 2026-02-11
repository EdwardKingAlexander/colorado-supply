<?php

namespace App\Filament\Resources\Manufacturers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManufacturerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('cage_code')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->extraInputAttributes(['class' => 'font-mono'])
                            ->nullable(),
                    ]),
            ]);
    }
}
