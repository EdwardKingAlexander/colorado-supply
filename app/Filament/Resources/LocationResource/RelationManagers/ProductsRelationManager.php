<?php

namespace App\Filament\Resources\LocationResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'locationProducts';

    protected static ?string $title = 'Products';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Product Assignment')
                ->schema([
                    Select::make('product_id')
                        ->label('Product')
                        ->relationship('product', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->helperText('Select product to assign to this location'),
                ])
                ->columns(1),

            Section::make('Inventory Settings')
                ->schema([
                    TextInput::make('bin_label')
                        ->maxLength(255)
                        ->placeholder('e.g., A1, Shelf 3-B, Room 201')
                        ->helperText('Physical location identifier for this product'),

                    TextInput::make('on_hand')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->suffix('units')
                        ->helperText('Current stock quantity'),

                    TextInput::make('reorder_point')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->suffix('units')
                        ->helperText('Trigger reorder when stock falls below this'),

                    TextInput::make('max_stock')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->suffix('units')
                        ->helperText('Maximum stock level for this location'),

                    Toggle::make('visible')
                        ->label('Visible to Users')
                        ->default(true)
                        ->helperText('When disabled, product will not appear for this location'),
                ])
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bin_label')
                    ->label('Bin/Location')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('on_hand')
                    ->label('On Hand')
                    ->numeric()
                    ->sortable()
                    ->suffix(' units')
                    ->color(fn ($record) => $record->on_hand <= $record->reorder_point ? 'warning' : null)
                    ->icon(fn ($record) => $record->on_hand <= $record->reorder_point ? 'heroicon-o-exclamation-triangle' : null)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('reorder_point')
                    ->label('Reorder Point')
                    ->numeric()
                    ->sortable()
                    ->suffix(' units')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('max_stock')
                    ->label('Max Stock')
                    ->numeric()
                    ->sortable()
                    ->suffix(' units')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('visible')
                    ->label('Visible')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('visible')
                    ->label('Visibility')
                    ->placeholder('All products')
                    ->trueLabel('Visible only')
                    ->falseLabel('Hidden only'),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn ($query) => $query->whereRaw('on_hand <= reorder_point'))
                    ->toggle(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Product')
                    ->icon('heroicon-o-plus'),

                Action::make('bulk_update_stock')
                    ->label('Bulk Update Stock')
                    ->icon('heroicon-o-arrows-pointing-in')
                    ->form([
                        Select::make('product_ids')
                            ->label('Products')
                            ->multiple()
                            ->relationship('locationProducts', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->product?->name)
                            ->required()
                            ->searchable()
                            ->preload(),

                        TextInput::make('quantity_change')
                            ->label('Quantity Change')
                            ->numeric()
                            ->required()
                            ->helperText('Positive to add, negative to subtract'),

                        Textarea::make('reason')
                            ->label('Reason')
                            ->rows(2)
                            ->placeholder('e.g., Received shipment, Cycle count adjustment, Damage'),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $productIds = $data['product_ids'];
                        $change = (int) $data['quantity_change'];

                        foreach ($productIds as $productId) {
                            $locationProduct = $livewire->getOwnerRecord()
                                ->locationProducts()
                                ->where('product_id', $productId)
                                ->first();

                            if ($locationProduct) {
                                $newOnHand = max(0, $locationProduct->on_hand + $change);
                                $locationProduct->update(['on_hand' => $newOnHand]);
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Stock updated successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                EditAction::make(),

                Action::make('adjust_stock')
                    ->label('Adjust')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->form([
                        TextInput::make('quantity_change')
                            ->label('Quantity Change')
                            ->numeric()
                            ->required()
                            ->helperText('Positive to add, negative to subtract'),

                        Textarea::make('reason')
                            ->label('Reason')
                            ->rows(2)
                            ->placeholder('e.g., Received shipment, Cycle count, Damage'),
                    ])
                    ->action(function ($record, array $data) {
                        $change = (int) $data['quantity_change'];
                        $newOnHand = max(0, $record->on_hand + $change);
                        $record->update(['on_hand' => $newOnHand]);

                        \Filament\Notifications\Notification::make()
                            ->title('Stock adjusted')
                            ->body("New quantity: {$newOnHand} units")
                            ->success()
                            ->send();
                    }),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    BulkAction::make('set_visible')
                        ->label('Mark as Visible')
                        ->icon('heroicon-o-eye')
                        ->action(fn ($records) => $records->each->update(['visible' => true]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('set_hidden')
                        ->label('Mark as Hidden')
                        ->icon('heroicon-o-eye-slash')
                        ->action(fn ($records) => $records->each->update(['visible' => false]))
                        ->requiresConfirmation()
                        ->color('warning')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
