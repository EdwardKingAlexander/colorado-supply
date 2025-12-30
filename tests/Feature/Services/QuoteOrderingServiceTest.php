<?php

use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteOrderingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('converts a quote into an order with matching items', function (): void {
    $salesRep = User::factory()->create();

    $quote = Quote::query()->create([
        'quote_number' => 'Q-TEST-001',
        'status' => 'sent',
        'customer_id' => null,
        'portal_user_id' => $salesRep->id,
        'currency' => 'USD',
        'tax_rate' => 0,
        'discount_amount' => 0,
        'subtotal' => 100,
        'tax_total' => 5,
        'grand_total' => 105,
        'sales_rep_id' => $salesRep->id,
    ]);

    $quote->items()->create([
        'product_id' => null,
        'sku' => 'SKU-123',
        'name' => 'Industrial Fan',
        'qty' => 2,
        'unit_price' => 50,
        'line_subtotal' => 100,
        'line_tax' => 5,
        'line_total' => 105,
    ]);

    $service = app(QuoteOrderingService::class);

    $order = $service->convert($quote, [
        'payment_method' => 'online_portal',
        'send_email' => false,
    ]);

    expect($order->quote_id)->toBe($quote->id);
    expect($order->items)->toHaveCount(1);
    expect($quote->fresh()->status)->toBe('ordered');
});
