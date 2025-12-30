<?php

namespace App\Filament\Resources\CRM;

use App\Filament\Resources\CRM\OrderResource\Pages;
use App\Filament\Resources\CRM\OrderResource\RelationManagers\ItemsRelationManager;
use App\Models\Order;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\UnitEnum|null $navigationGroup = 'CRM';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Order Overview')
                ->schema([
                    Grid::make(2)->schema([
                        \Filament\Forms\Components\TextInput::make('order_number')
                            ->disabled()
                            ->label('Order #'),
                        \Filament\Forms\Components\TextInput::make('status')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('payment_status')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('fulfillment_status')
                            ->disabled(),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record])),
                TextColumn::make('quote.quote_number')
                    ->label('Quote #')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('portalUser.name')
                    ->label('Portal User')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->toggleable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'confirmed',
                        'danger' => 'cancelled',
                    ]),
                TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
