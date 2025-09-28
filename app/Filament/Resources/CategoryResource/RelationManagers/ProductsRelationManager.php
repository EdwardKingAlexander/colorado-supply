<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
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

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('vendor_id')
                ->label('Vendor')
                ->relationship('vendor', 'name')
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('slug')->maxLength(255),
            Forms\Components\TextInput::make('sku')->maxLength(100)->required(),
            Forms\Components\TextInput::make('price')->numeric()->prefix('$'),
            Forms\Components\TextInput::make('stock')->numeric()->minValue(0)->default(0),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sku')->label('SKU')->toggleable(),
                Tables\Columns\TextColumn::make('vendor.name')->label('Vendor')->sortable(),
                Tables\Columns\TextColumn::make('price')->money('usd')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('M j, Y')->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['category_id'] = $this->getOwnerRecord()->getKey();
        return $data;
    }
}
