<?php

use App\Events\QuoteConvertedToOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteOrderingService;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'sales_reps', 'guard_name' => 'web']);
});

test('walk-in quote requires walk-in fields when no customer', function () {
    $user = User::factory()->create();

    // Create quote without customer (walk-in)
    $quote = Quote::factory()->create([
        'customer_id' => null,
        'walk_in_label' => 'cash/card',
        'walk_in_contact_name' => 'John Doe',
        'walk_in_email' => 'john@example.com',
        'sales_rep_id' => $user->id,
    ]);

    expect($quote->isWalkIn())->toBeTrue();
    expect($quote->walk_in_contact_name)->toBe('John Doe');
});

test('quote conversion accepts allowed payment methods', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $quote = Quote::factory()->create([
        'customer_id' => $customer->id,
        'sales_rep_id' => $user->id,
        'grand_total' => 1000.00,
        'status' => 'draft',
    ]);

    $orderingService = new QuoteOrderingService();

    $allowedMethods = ['credit_card', 'debit_card', 'online_portal'];

    foreach ($allowedMethods as $method) {
        $testQuote = Quote::factory()->create([
            'customer_id' => $customer->id,
            'sales_rep_id' => $user->id,
            'grand_total' => 1000.00,
            'status' => 'draft',
        ]);

        Event::fake();

        $order = $orderingService->convert($testQuote, [
            'payment_method' => $method,
            'po_number' => 'PO-12345',
            'job_number' => 'JOB-67890',
            'notes' => 'Test order',
        ]);

        expect($order)->toBeInstanceOf(Order::class);
        expect($order->payment_method)->toBe($method);
        expect($order->order_total)->toBe(1000.00);
        expect($testQuote->fresh()->status)->toBe('ordered');

        Event::assertDispatched(QuoteConvertedToOrder::class);
    }
});

test('quote conversion rejects disallowed payment methods', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $quote = Quote::factory()->create([
        'customer_id' => $customer->id,
        'sales_rep_id' => $user->id,
        'grand_total' => 1000.00,
        'status' => 'draft',
    ]);

    $orderingService = new QuoteOrderingService();

    $disallowedMethods = ['cash', 'check', 'terms', 'invoice'];

    foreach ($disallowedMethods as $method) {
        try {
            $orderingService->convert($quote, [
                'payment_method' => $method,
            ]);
            expect(false)->toBeTrue('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            expect($e)->toBeInstanceOf(ValidationException::class);
            expect($e->errors())->toHaveKey('payment_method');
        }
    }
});

test('quote conversion creates order with correct totals and data', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $quote = Quote::factory()->create([
        'customer_id' => $customer->id,
        'sales_rep_id' => $user->id,
        'subtotal' => 1000.00,
        'tax_total' => 80.00,
        'grand_total' => 1080.00,
        'status' => 'draft',
    ]);

    $orderingService = new QuoteOrderingService();

    $order = $orderingService->convert($quote, [
        'payment_method' => 'credit_card',
        'po_number' => 'PO-TEST-123',
        'job_number' => 'JOB-TEST-456',
        'notes' => 'Test conversion',
    ]);

    expect($order->quote_id)->toBe($quote->id);
    expect($order->customer_id)->toBe($customer->id);
    expect($order->order_total)->toBe(1080.00);
    expect($order->payment_method)->toBe('credit_card');
    expect($order->po_number)->toBe('PO-TEST-123');
    expect($order->job_number)->toBe('JOB-TEST-456');
    expect($order->notes)->toBe('Test conversion');
    expect($order->status)->toBe('created');

    // Verify quote status updated
    expect($quote->fresh()->status)->toBe('ordered');
});

test('quote conversion copies walk-in details to order', function () {
    $user = User::factory()->create();
    $quote = Quote::factory()->create([
        'customer_id' => null,
        'sales_rep_id' => $user->id,
        'walk_in_label' => 'cash/card',
        'walk_in_org' => 'Test Org',
        'walk_in_contact_name' => 'Jane Doe',
        'walk_in_email' => 'jane@test.com',
        'walk_in_phone' => '555-1234',
        'walk_in_billing_json' => ['street' => '123 Main St'],
        'walk_in_shipping_json' => ['street' => '456 Oak Ave'],
        'grand_total' => 500.00,
        'status' => 'draft',
    ]);

    $orderingService = new QuoteOrderingService();

    $order = $orderingService->convert($quote, [
        'payment_method' => 'debit_card',
    ]);

    expect($order->walk_in_label)->toBe('cash/card');
    expect($order->walk_in_org)->toBe('Test Org');
    expect($order->walk_in_contact_name)->toBe('Jane Doe');
    expect($order->walk_in_email)->toBe('jane@test.com');
    expect($order->walk_in_phone)->toBe('555-1234');
    expect($order->walk_in_billing_json)->toBe(['street' => '123 Main St']);
    expect($order->walk_in_shipping_json)->toBe(['street' => '456 Oak Ave']);
});
