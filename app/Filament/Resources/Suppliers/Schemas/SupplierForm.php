<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('cage_code')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->extraInputAttributes(['class' => 'font-mono'])
                            ->nullable(),
                        Textarea::make('contact_info')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->nullable(),
                        TextInput::make('website')
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->nullable(),
                    ]),
            ]);
    }
}
