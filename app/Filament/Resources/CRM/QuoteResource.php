<?php

namespace App\Filament\Resources\CRM;

use App\Filament\Resources\CRM\QuoteResource\Pages;
use App\Filament\Resources\CRM\QuoteResource\RelationManagers\ItemsRelationManager;
use App\Models\Product;
use App\Models\Quote;
use App\Services\QuoteOrderingService;
use App\Services\QuoteTotalsService;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static string|\UnitEnum|null $navigationGroup = 'CRM';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Quotes';

    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', Quote::class);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Quote Information')
                ->columns(2)
                ->schema([
                    TextInput::make('quote_number')
                        ->label('Quote Number')
                        ->disabled()
                        ->dehydrated(false)
                        ->default(fn () => 'Q-'.strtoupper(uniqid())),

                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'sent' => 'Sent',
                            'ordered' => 'Ordered',
                            'cancelled' => 'Cancelled',
                            'expired' => 'Expired',
                        ])
                        ->default('draft')
                        ->required(),

                    Select::make('sales_rep_id')
                        ->label('Sales Rep')
                        ->relationship('salesRep', 'name')
                        ->searchable()
                        ->preload()
                        ->default(fn () => auth()->id())
                        ->required(),
                ]),

            Section::make('Customer / Walk-In')
                ->columns(2)
                ->schema([
                    Toggle::make('is_walk_in')
                        ->label('Walk-In (Cash/Card)')
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $set('customer_id', null);
                            } else {
                                $set('walk_in_org', null);
                                $set('walk_in_contact_name', null);
                                $set('walk_in_email', null);
                                $set('walk_in_phone', null);
                            }
                        })
                        ->columnSpanFull(),

                    Select::make('customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn (callable $get) => ! $get('is_walk_in'))
                        ->requiredUnless('is_walk_in', true)
                        ->columnSpanFull(),

                    TextInput::make('walk_in_label')
                        ->label('Walk-In Label')
                        ->default('cash/card')
                        ->visible(fn (callable $get) => $get('is_walk_in'))
                        ->required(fn (callable $get) => $get('is_walk_in')),

                    TextInput::make('walk_in_org')
                        ->label('Organization')
                        ->visible(fn (callable $get) => $get('is_walk_in')),

                    TextInput::make('walk_in_contact_name')
                        ->label('Contact Name')
                        ->visible(fn (callable $get) => $get('is_walk_in'))
                        ->required(fn (callable $get) => $get('is_walk_in')),

                    TextInput::make('walk_in_email')
                        ->label('Email')
                        ->email()
                        ->visible(fn (callable $get) => $get('is_walk_in')),

                    TextInput::make('walk_in_phone')
                        ->label('Phone')
                        ->tel()
                        ->visible(fn (callable $get) => $get('is_walk_in')),

                    Textarea::make('walk_in_billing_json')
                        ->label('Billing Address (JSON)')
                        ->visible(fn (callable $get) => $get('is_walk_in'))
                        ->columnSpanFull(),

                    Textarea::make('walk_in_shipping_json')
                        ->label('Shipping Address (JSON)')
                        ->visible(fn (callable $get) => $get('is_walk_in'))
                        ->columnSpanFull(),
                ]),

            Section::make('Line Items')
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->options(Product::pluck('name', 'id'))
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('sku', $product->sku);
                                            $set('name', $product->name);
                                            $set('unit_price', $product->price ?? 0);
                                        }
                                    }
                                }),

                            TextInput::make('sku')
                                ->label('SKU'),

                            TextInput::make('name')
                                ->label('Item Name')
                                ->required(),

                            TextInput::make('qty')
                                ->label('Quantity')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(0.01),

                            TextInput::make('unit_price')
                                ->label('Unit Price')
                                ->numeric()
                                ->required()
                                ->prefix('$')
                                ->minValue(0),

                            TextInput::make('uom')
                                ->label('Unit of Measure'),

                            Textarea::make('notes')
                                ->label('Item Notes')
                                ->columnSpanFull(),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Add Item')
                        ->columnSpanFull()
                        ->afterStateUpdated(function ($state, callable $set, $record) {
                            if ($record && $record->exists) {
                                app(QuoteTotalsService::class)->recalculateTotals($record);
                                $record->refresh();
                            }
                        }),
                ]),

            Section::make('Pricing')
                ->columns(3)
                ->schema([
                    TextInput::make('tax_rate')
                        ->label('Tax Rate (%)')
                        ->numeric()
                        ->default(0)
                        ->suffix('%')
                        ->minValue(0),

                    TextInput::make('discount_amount')
                        ->label('Discount Amount')
                        ->numeric()
                        ->default(0)
                        ->prefix('$')
                        ->minValue(0),

                    TextInput::make('currency')
                        ->label('Currency')
                        ->default('USD')
                        ->required(),

                    TextInput::make('subtotal')
                        ->label('Subtotal')
                        ->prefix('$')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('tax_total')
                        ->label('Tax Total')
                        ->prefix('$')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('grand_total')
                        ->label('Grand Total')
                        ->prefix('$')
                        ->disabled()
                        ->dehydrated(false),
                ]),

            Section::make('Additional Information')
                ->schema([
                    Textarea::make('notes')
                        ->label('Notes')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quote_number')
                    ->label('Quote #')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->url(fn (Quote $record) => QuoteResource::getUrl('edit', ['record' => $record])),

                TextColumn::make('customerDisplayName')
                    ->label('Customer / Walk-In')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search) {
                            $query->whereHas('customer', function (Builder $query) use ($search) {
                                $query->where('name', 'like', "%{$search}%");
                            })
                                ->orWhere('walk_in_label', 'like', "%{$search}%")
                                ->orWhere('walk_in_org', 'like', "%{$search}%");
                        });
                    }),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'draft',
                        'primary' => 'sent',
                        'success' => 'ordered',
                        'danger' => 'cancelled',
                        'warning' => 'expired',
                    ]),

                TextColumn::make('salesRep.name')
                    ->label('Sales Rep')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('portalUser.name')
                    ->label('Portal User')
                    ->sortable()
                    ->searchable()
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
            ->filters([
                SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'ordered' => 'Ordered',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                    ]),

                Filter::make('my_quotes')
                    ->label('My Quotes')
                    ->query(fn (Builder $query): Builder => $query->where('sales_rep_id', auth()->id())),

                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                static::convertToOrderAction(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                \Filament\Actions\BulkAction::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($records) {
                        $filename = 'quotes-'.now()->format('Y-m-d-His').'.csv';
                        $headers = [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                        ];

                        $callback = function () use ($records) {
                            $file = fopen('php://output', 'w');
                            fputcsv($file, ['Quote #', 'Status', 'Customer', 'Sales Rep', 'Total', 'Created At']);

                            foreach ($records as $record) {
                                fputcsv($file, [
                                    $record->quote_number,
                                    $record->status,
                                    $record->customerDisplayName,
                                    $record->salesRep->name ?? '',
                                    $record->grand_total,
                                    $record->created_at->format('Y-m-d H:i:s'),
                                ]);
                            }

                            fclose($file);
                        };

                        return response()->stream($callback, 200, $headers);
                    }),
            ]);
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
            'index' => Pages\ListQuotes::route('/'),
            'create' => Pages\CreateQuote::route('/create'),
            'view' => Pages\ViewQuote::route('/{record}'),
            'edit' => Pages\EditQuote::route('/{record}/edit'),
        ];
    }

    public static function convertToOrderAction(): Action
    {
        return Action::make('convertToOrder')
            ->label('Convert to Order')
            ->icon('heroicon-o-shopping-cart')
            ->color('success')
            ->visible(fn (Quote $record) => $record->status !== 'ordered')
            ->authorize('convertToOrder')
            ->form([
                Select::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'online_portal' => 'Online Portal',
                        'credit_card' => 'Credit Card',
                        'debit_card' => 'Debit Card',
                    ])
                    ->required(),
                TextInput::make('po_number')
                    ->label('PO Number')
                    ->maxLength(50),
                TextInput::make('job_number')
                    ->label('Job Number')
                    ->maxLength(50),
                Textarea::make('notes')
                    ->label('Internal Notes')
                    ->rows(3)
                    ->maxLength(500),
                Toggle::make('send_email')
                    ->label('Email customer confirmation')
                    ->default(true),
            ])
            ->action(function (Quote $record, array $data) {
                try {
                    $order = app(QuoteOrderingService::class)->convert($record, $data);

                    Notification::make()
                        ->success()
                        ->title('Order '.$order->order_number.' created')
                        ->body('Quote converted and ready for fulfillment.')
                        ->send();

                    return redirect(OrderResource::getUrl('view', ['record' => $order]));
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Conversion failed')
                        ->body($exception->getMessage())
                        ->send();
                }
            });
    }
}
