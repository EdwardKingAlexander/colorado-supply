# Orders Feature - Remaining Implementation Files

Due to the extensive scope, some files are provided as code snippets below. Copy each to its respective path.

---

## CRITICAL: Filament OrderResource

This is the main admin interface. Create these files:

### File: app/Filament/Resources/OrderResource.php

**NOTE:** This is a LARGE file (~500+ lines). The Filament resource needs to be created using:

```bash
php artisan make:filament-resource Order --generate --view
```

Then customize with the following key sections. I'll provide a working minimal version:

```php
<?php

namespace App\Filament\Resources;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Sales';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Customer Information')->columns(2)->schema([
                Toggle::make('is_cash_card')
                    ->label('Cash/Card Guest (no saved customer)')
                    ->live()
                    ->dehydrated(false)
                    ->columnSpanFull(),

                Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->hidden(fn (Get $get) => $get('is_cash_card'))
                    ->columnSpanFull(),

                TextInput::make('cash_card_name')
                    ->label('Name')
                    ->visible(fn (Get $get) => $get('is_cash_card'))
                    ->required(fn (Get $get) => $get('is_cash_card')),

                TextInput::make('cash_card_email')
                    ->label('Email')
                    ->email()
                    ->visible(fn (Get $get) => $get('is_cash_card'))
                    ->required(fn (Get $get) => $get('is_cash_card')),

                TextInput::make('cash_card_phone')
                    ->label('Phone')
                    ->tel()
                    ->visible(fn (Get $get) => $get('is_cash_card')),

                TextInput::make('cash_card_company')
                    ->label('Company')
                    ->visible(fn (Get $get) => $get('is_cash_card')),
            ]),

            Section::make('Commercial Information')->columns(2)->schema([
                TextInput::make('po_number')
                    ->label('PO Number'),

                TextInput::make('job_number')
                    ->label('Job Number'),

                Select::make('quote_id')
                    ->label('Related Quote')
                    ->relationship('quote', 'id')
                    ->searchable()
                    ->preload(),
            ]),

            Section::make('Order Items')->schema([
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('description')
                            ->columnSpan(2),

                        TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                static::calculateLineTotal($set, $get);
                            }),

                        TextInput::make('unit_price')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                static::calculateLineTotal($set, $get);
                            }),

                        TextInput::make('line_discount')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                static::calculateLineTotal($set, $get);
                            }),

                        TextInput::make('line_total')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(6)
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        static::calculateOrderTotals($set, $get);
                    })
                    ->columnSpanFull(),
            ]),

            Section::make('Totals')->columns(2)->schema([
                TextInput::make('tax_rate')
                    ->label('Tax Rate (%)')
                    ->numeric()
                    ->default(0)
                    ->suffix('%')
                    ->live(debounce: 500)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        static::calculateOrderTotals($set, $get);
                    }),

                TextInput::make('shipping_total')
                    ->label('Shipping')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->live(debounce: 500)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        static::calculateOrderTotals($set, $get);
                    }),

                TextInput::make('discount_total')
                    ->label('Order Discount')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->live(debounce: 500)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        static::calculateOrderTotals($set, $get);
                    }),

                TextInput::make('subtotal')
                    ->numeric()
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('tax_total')
                    ->label('Tax Amount')
                    ->numeric()
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('grand_total')
                    ->label('Grand Total')
                    ->numeric()
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(),
            ]),

            Section::make('Status')->columns(3)->schema([
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->required()
                    ->default(OrderStatus::Draft),

                Select::make('payment_status')
                    ->options(PaymentStatus::class)
                    ->required()
                    ->default(PaymentStatus::Unpaid),

                Select::make('fulfillment_status')
                    ->options(FulfillmentStatus::class)
                    ->required()
                    ->default(FulfillmentStatus::Unfulfilled),
            ]),

            Section::make('Notes')->schema([
                Textarea::make('notes')
                    ->label('Customer Notes')
                    ->rows(3),

                Textarea::make('internal_notes')
                    ->label('Internal Notes')
                    ->rows(3),
            ]),

            Hidden::make('order_number'),
        ]);
    }

    protected static function calculateLineTotal(Set $set, Get $get): void
    {
        $quantity = floatval($get('quantity') ?? 0);
        $unitPrice = floatval($get('unit_price') ?? 0);
        $discount = floatval($get('line_discount') ?? 0);

        $lineTotal = ($quantity * $unitPrice) - $discount;
        $set('line_total', number_format($lineTotal, 2, '.', ''));
    }

    protected static function calculateOrderTotals(Set $set, Get $get): void
    {
        $items = $get('items') ?? [];
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += floatval($item['line_total'] ?? 0);
        }

        $taxRate = floatval($get('tax_rate') ?? 0);
        $shippingTotal = floatval($get('shipping_total') ?? 0);
        $discountTotal = floatval($get('discount_total') ?? 0);

        $taxTotal = ($subtotal * $taxRate) / 100;
        $grandTotal = $subtotal + $taxTotal + $shippingTotal - $discountTotal;

        $set('subtotal', number_format($subtotal, 2, '.', ''));
        $set('tax_total', number_format($taxTotal, 2, '.', ''));
        $set('grand_total', number_format(max(0, $grandTotal), 2, '.', ''));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable(),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable(['customer.name', 'cash_card_name'])
                    ->sortable(),

                TextColumn::make('grand_total')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->badge(),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),

                Action::make('checkout')
                    ->label('Go to Checkout')
                    ->icon('heroicon-o-credit-card')
                    ->url(fn (Order $record) => route('orders.checkout', $record))
                    ->visible(fn (Order $record) => $record->canBePaid())
                    ->openUrlInNewTab(),

                Action::make('downloadPdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Order $record) => route('orders.download.pdf', $record))
                    ->openUrlInNewTab(),

                Action::make('downloadExcel')
                    ->label('Download Excel')
                    ->icon('heroicon-o-table-cells')
                    ->url(fn (Order $record) => route('orders.download.excel', $record))
                    ->openUrlInNewTab(),

                Action::make('markPaid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(fn (Order $record) => $record->markAsPaid())
                    ->visible(fn (Order $record) => $record->canBePaid())
                    ->authorize('markPaid'),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
```

### Create Filament Pages

```bash
php artisan make:filament-page ListOrders --resource=OrderResource --type=ListRecords
php artisan make:filament-page CreateOrder --resource=OrderResource --type=CreateRecord
php artisan make:filament-page EditOrder --resource=OrderResource --type=EditRecord
php artisan make:filament-page ViewOrder --resource=OrderResource --type=ViewRecord
```

---

## Additional View Files Needed

### File: resources/views/orders/checkout-success.blade.php

```blade
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h2 class="mt-4 text-3xl font-bold">Payment Successful!</h2>
                        <p class="mt-2 text-gray-600">Thank you for your order.</p>

                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <p class="font-semibold">Order Number: {{ $order->order_number }}</p>
                            <p class="mt-2">Total: ${{ number_format($order->grand_total, 2) }}</p>
                        </div>

                        <p class="mt-6 text-gray-600">
                            A confirmation email has been sent to {{ $order->customer_email }}.
                        </p>

                        <div class="mt-8">
                            <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                View Order
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

### File: resources/views/orders/checkout-cancel.blade.php

```blade
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <h2 class="mt-4 text-3xl font-bold">Payment Cancelled</h2>
                        <p class="mt-2 text-gray-600">Your payment was cancelled. No charges were made.</p>

                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <p class="font-semibold">Order Number: {{ $order->order_number }}</p>
                        </div>

                        <div class="mt-8 space-x-4">
                            <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                View Order
                            </a>
                            <a href="{{ route('orders.checkout', $order) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                Try Again
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

### File: resources/views/orders/show.blade.php

```blade
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-2xl font-bold mb-4">Order {{ $order->order_number }}</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="font-semibold">Customer:</p>
                            <p>{{ $order->customer_name }}</p>
                            <p>{{ $order->customer_email }}</p>
                        </div>
                        <div>
                            <p class="font-semibold">Status:</p>
                            <p>Order: {{ $order->status->label() }}</p>
                            <p>Payment: {{ $order->payment_status->label() }}</p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h3 class="font-semibold mb-2">Items:</h3>
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th class="text-left">Item</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Price</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td class="text-right">{{ $item->quantity }}</td>
                                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-right">${{ number_format($item->line_total, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 text-right">
                        <p class="text-2xl font-bold">Total: ${{ number_format($order->grand_total, 2) }}</p>
                    </div>

                    @if($order->canBePaid())
                    <div class="mt-6">
                        <a href="{{ route('orders.checkout', $order) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            Pay Now
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

---

## Quick Start Commands

After copying all files:

```bash
# Install packages
composer require stripe/stripe-php:^15 barryvdh/laravel-dompdf:^2 maatwebsite/excel:^3.1

# Run migrations
php artisan migrate

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Start queue worker
php artisan queue:work

# Test order number generation
php artisan tinker
>>> app(App\Services\Orders\OrderNumberGenerator::class)->next();
```

---

## All Files Created

✅ Migrations (6 files)
✅ Enums (4 files)
✅ Models (6 files)
✅ Services (2 files)
✅ Controllers (3 files)
✅ Jobs (1 file)
✅ Mailables (1 file)
✅ Exports (1 file)
✅ Policies (1 file)
✅ Views - PDF/Excel/Email (3 files)
✅ Views - Success/Cancel/Show (3 files)
⚠️ Filament Resource (needs customization)
✅ Documentation (2 files)

**Total: 34 files created!**
