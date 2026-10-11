<?php

namespace App\Filament\Resources\CRM;

use App\Enums\FulfillmentMethod;
use App\Enums\PickupStatus;
use App\Filament\Resources\CRM\OrderResource\Pages;
use App\Filament\Resources\CRM\OrderResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\CRM\OrderResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\CRM\OrderResource\WillCallActions;
use App\Models\Order;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                        TextInput::make('order_number')
                            ->disabled()
                            ->label('Order #'),
                        TextInput::make('status')
                            ->disabled(),
                        TextInput::make('payment_status')
                            ->disabled(),
                        TextInput::make('fulfillment_status')
                            ->disabled(),
                    ]),
                ]),

            Section::make('Will call pickup')
                ->visible(fn (?Order $record) => $record?->isWillCall())
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('pickup_location')
                            ->label('Pickup location')
                            ->state(fn (Order $record) => $record->pickupLocation?->name ?? '—'),
                        TextEntry::make('pickup_status_label')
                            ->label('Pickup status')
                            ->state(fn (Order $record) => $record->pickup_status?->label() ?? '—'),
                        TextEntry::make('pickup_code_value')
                            ->label('Pickup code')
                            ->state(fn (Order $record) => $record->pickup_code ?? 'Generated when marked ready'),
                        TextEntry::make('ready_at')
                            ->label('Ready since')
                            ->state(fn (Order $record) => $record->ready_for_pickup_at?->format('M j, Y g:i A') ?? '—'),
                        TextEntry::make('picked_up')
                            ->label('Picked up')
                            ->state(fn (Order $record) => $record->picked_up_at
                                ? $record->picked_up_at->format('M j, Y g:i A').' by '.$record->picked_up_by
                                : '—'),
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
                TextColumn::make('fulfillment_method')
                    ->label('Method')
                    ->badge()
                    ->formatStateUsing(fn (FulfillmentMethod $state) => $state->label())
                    ->color(fn (FulfillmentMethod $state) => $state->color())
                    ->toggleable(),
                TextColumn::make('pickup_status')
                    ->label('Pickup')
                    ->badge()
                    ->formatStateUsing(fn (?PickupStatus $state) => $state?->label())
                    ->color(fn (?PickupStatus $state) => $state?->color() ?? 'gray')
                    ->description(fn (Order $record) => $record->pickupLocation?->name)
                    ->placeholder('—')
                    ->toggleable(),
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with('pickupLocation'))
            ->filters([
                SelectFilter::make('fulfillment_method')
                    ->label('Method')
                    ->options(collect(FulfillmentMethod::cases())->mapWithKeys(fn (FulfillmentMethod $case) => [$case->value => $case->label()])),
                SelectFilter::make('pickup_status')
                    ->label('Pickup status')
                    ->options(collect(PickupStatus::cases())->mapWithKeys(fn (PickupStatus $case) => [$case->value => $case->label()])),
                SelectFilter::make('pickup_location_id')
                    ->label('Pickup location')
                    ->relationship('pickupLocation', 'name', fn (Builder $query) => $query->where('pickup_enabled', true)),
            ])
            ->actions(WillCallActions::make())
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            PaymentsRelationManager::class,
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
