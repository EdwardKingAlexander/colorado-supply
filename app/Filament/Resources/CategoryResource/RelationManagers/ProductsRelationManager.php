<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';
    protected static ?string $recordTitleAttribute = 'name';

    public function getTableQuery(): Builder
    {
        return $this->getOwnerRecord()
            ->products()
            ->getQuery()
            ->where('category_id', $this->getOwnerRecord()->getKey());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('vendor_id')
                ->label('Vendor')
                ->relationship('vendor', 'name')
                ->searchable()
                ->required(),

            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('slug')->maxLength(255),
            TextInput::make('sku')->maxLength(100)->required(),
            TextInput::make('price')->numeric()->prefix('$'),
            TextInput::make('stock')->numeric()->minValue(0)->default(0),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('sku')->label('SKU')->toggleable(),
                TextColumn::make('vendor.name')->label('Vendor')->sortable(),
                TextColumn::make('price')->money('usd')->sortable(),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('created_at')->dateTime('M j, Y')->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['category_id'] = $this->getOwnerRecord()->getKey();
        return $data;
    }
}
