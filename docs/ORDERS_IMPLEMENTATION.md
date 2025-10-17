# Orders Feature - Complete Implementation Guide

## Generated Files Summary

This document contains all the code needed to implement the production-ready Orders feature. Due to size constraints, copy each code block to its respective file path.

---

## File: app/Services/Orders/PlaceOrderFromQuote.php

```php
<?php

namespace App\Services\Orders;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

class PlaceOrderFromQuote
{
    public function __construct(
        private OrderNumberGenerator $orderNumberGenerator
    ) {
    }

    /**
     * Convert a quote to an order.
     */
    public function execute(Quote $quote, array $overrides = []): Order
    {
        return DB::transaction(function () use ($quote, $overrides) {
            // Generate unique order number
            $orderNumber = $this->orderNumberGenerator->next();

            // Create order from quote
            $order = Order::create(array_merge([
                'order_number' => $orderNumber,
                'quote_id' => $quote->id,
                'customer_id' => $quote->customer_id,

                // Copy contact info
                'contact_name' => $quote->customer?->name,
                'contact_email' => $quote->customer?->email,
                'contact_phone' => $quote->customer?->phone,
                'company_name' => $quote->customer?->company,

                // Copy addresses (if available on quote)
                'billing_address' => $quote->billing_address ?? $quote->customer?->billing_address,
                'shipping_address' => $quote->shipping_address ?? $quote->customer?->shipping_address,

                // Copy commercial fields
                'po_number' => $quote->po_number ?? null,
                'job_number' => $quote->job_number ?? null,
                'notes' => $quote->notes,

                // Copy totals
                'subtotal' => $quote->subtotal,
                'tax_total' => $quote->tax_total,
                'discount_total' => $quote->discount_total ?? 0,
                'grand_total' => $quote->grand_total,
                'tax_rate' => $quote->tax_rate ?? 0,

                // Set statuses
                'status' => OrderStatus::Confirmed,
                'payment_status' => PaymentStatus::Unpaid,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'confirmed_at' => now(),

                // Metadata
                'meta' => [
                    'allowed_payment_methods' => ['card', 'online'],
                    'converted_from_quote' => $quote->id,
                ],
            ], $overrides));

            // Copy quote items to order items
            foreach ($quote->items as $quoteItem) {
                $order->items()->create([
                    'product_id' => $quoteItem->product_id ?? null,
                    'sku' => $quoteItem->sku,
                    'name' => $quoteItem->name,
                    'description' => $quoteItem->description,
                    'quantity' => $quoteItem->quantity,
                    'unit_price' => $quoteItem->unit_price,
                    'line_discount' => $quoteItem->line_discount ?? 0,
                    'line_total' => $quoteItem->line_total,
                ]);
            }

            // Recalculate totals to ensure accuracy
            $order->refresh();
            $order->recalcTotals();
            $order->save();

            return $order;
        });
    }
}
```

---

## File: app/Providers/StripeServiceProvider.php

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class StripeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function ($app) {
            $secretKey = config('services.stripe.secret_key');

            if (empty($secretKey)) {
                throw new \RuntimeException('Stripe secret key is not configured. Set STRIPE_SECRET_KEY in .env');
            }

            return new StripeClient($secretKey);
        });
    }

    public function boot(): void
    {
        //
    }
}
```

Add to `bootstrap/providers.php`:
```php
App\Providers\StripeServiceProvider::class,
```

---

## File: config/services.php (add Stripe config)

```php
'stripe' => [
    'public_key' => env('STRIPE_PUBLIC_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],
```

---

## File: app/Http/Controllers/CheckoutController.php

```php
<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    public function __construct(
        private StripeClient $stripe
    ) {
    }

    /**
     * Create a Stripe Checkout Session for an order.
     */
    public function createCheckoutSession(Order $order): JsonResponse
    {
        // Verify order can be paid
        if (! $order->canBePaid()) {
            return response()->json([
                'error' => 'This order cannot be paid. It may already be paid or refunded.',
            ], 422);
        }

        // Recalculate totals to ensure accuracy
        $order->recalcTotals();
        $order->save();

        // Build line items for Stripe
        $lineItems = [];
        foreach ($order->items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item->name,
                        'description' => $item->description ?? '',
                    ],
                    'unit_amount' => (int) ($item->unit_price * 100), // Convert to cents
                ],
                'quantity' => (int) $item->quantity,
            ];
        }

        // Add tax as a line item if applicable
        if ($order->tax_total > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Tax',
                    ],
                    'unit_amount' => (int) ($order->tax_total * 100),
                ],
                'quantity' => 1,
            ];
        }

        // Add shipping as a line item if applicable
        if ($order->shipping_total > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Shipping',
                    ],
                    'unit_amount' => (int) ($order->shipping_total * 100),
                ],
                'quantity' => 1,
            ];
        }

        // Apply discount if applicable
        if ($order->discount_total > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Discount',
                    ],
                    'unit_amount' => -1 * (int) ($order->discount_total * 100),
                ],
                'quantity' => 1,
            ];
        }

        // Create Checkout Session
        try {
            $session = $this->stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => route('orders.checkout.success', ['order' => $order->id]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('orders.checkout.cancel', ['order' => $order->id]),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
                'customer_email' => $order->customer_email,
            ]);

            // Create pending payment record
            $order->payments()->create([
                'method' => PaymentMethod::Card,
                'status' => PaymentStatus::Pending,
                'amount' => $order->grand_total,
                'currency' => 'USD',
                'gateway' => 'stripe',
                'gateway_session_id' => $session->id,
            ]);

            // Update order payment status to pending
            $order->update(['payment_status' => PaymentStatus::Pending]);

            return response()->json([
                'url' => $session->url,
                'session_id' => $session->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create checkout session: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle successful checkout.
     */
    public function success(Order $order): \Illuminate\View\View
    {
        return view('orders.checkout-success', compact('order'));
    }

    /**
     * Handle cancelled checkout.
     */
    public function cancel(Order $order): \Illuminate\View\View
    {
        return view('orders.checkout-cancel', compact('order'));
    }
}
```

---

## File: app/Http/Controllers/StripeWebhookController.php

```php
<?php

namespace App\Http\Controllers;

use App\Jobs\SendOrderConfirmationEmail;
use App\Models\Order;
use App\Models\StripeEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        // Verify webhook signature
        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response('Invalid signature', 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook error', [
                'error' => $e->getMessage(),
            ]);

            return response('Webhook error', 400);
        }

        // Check if event has already been processed (idempotency)
        if (StripeEvent::isProcessed($event->id)) {
            Log::info('Stripe event already processed', ['event_id' => $event->id]);

            return response('Event already processed', 200);
        }

        // Store event for idempotency
        $stripeEvent = StripeEvent::create([
            'stripe_event_id' => $event->id,
            'type' => $event->type,
            'payload' => json_decode($payload, true),
        ]);

        // Handle the event
        try {
            match ($event->type) {
                'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event),
                'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event),
                default => Log::info('Unhandled Stripe webhook event', ['type' => $event->type]),
            };

            // Mark as processed
            $stripeEvent->markAsProcessed();
        } catch (\Exception $e) {
            Log::error('Failed to process Stripe webhook', [
                'event_id' => $event->id,
                'type' => $event->type,
                'error' => $e->getMessage(),
            ]);

            return response('Failed to process event', 500);
        }

        return response('Webhook handled', 200);
    }

    private function handleCheckoutSessionCompleted($event): void
    {
        $session = $event->data->object;
        $orderId = $session->metadata->order_id ?? null;

        if (! $orderId) {
            Log::warning('Checkout session completed without order_id', [
                'session_id' => $session->id,
            ]);

            return;
        }

        $order = Order::find($orderId);

        if (! $order) {
            Log::warning('Order not found for checkout session', [
                'order_id' => $orderId,
                'session_id' => $session->id,
            ]);

            return;
        }

        // Check if PaymentIntent is paid
        $paymentIntentId = $session->payment_intent;

        if ($paymentIntentId) {
            $stripe = app(\Stripe\StripeClient::class);
            $paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId);

            if ($paymentIntent->status === 'succeeded') {
                // Find and update payment record
                $payment = $order->payments()
                    ->where('gateway_session_id', $session->id)
                    ->first();

                if ($payment) {
                    $payment->update([
                        'gateway_payment_intent_id' => $paymentIntentId,
                        'gateway_charge_id' => $paymentIntent->latest_charge ?? null,
                    ]);
                    $payment->markAsPaid();
                }

                // Mark order as paid
                $order->markAsPaid();

                // Dispatch email job
                SendOrderConfirmationEmail::dispatch($order);

                Log::info('Order marked as paid via webhook', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);
            }
        }
    }

    private function handlePaymentIntentFailed($event): void
    {
        $paymentIntent = $event->data->object;

        // Find order by payment intent ID
        $payment = \App\Models\Payment::where('gateway_payment_intent_id', $paymentIntent->id)->first();

        if (! $payment) {
            Log::warning('Payment not found for failed payment intent', [
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        $order = $payment->order;

        // Mark payment as failed
        $payment->markAsFailed(
            $paymentIntent->last_payment_error->code ?? null,
            $paymentIntent->last_payment_error->message ?? 'Payment failed'
        );

        // Mark order payment as failed
        $order->markPaymentAsFailed();

        Log::info('Order payment marked as failed via webhook', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'error_code' => $paymentIntent->last_payment_error->code ?? null,
        ]);
    }
}
```

---

## File: routes/web.php (add these routes)

```php
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderExportController;
use App\Http\Controllers\StripeWebhookController;

// Orders
Route::middleware(['auth'])->prefix('orders')->name('orders.')->group(function () {
    Route::get('/{order}', function (Order $order) {
        return view('orders.show', compact('order'));
    })->name('show');

    Route::post('/{order}/checkout', [CheckoutController::class, 'createCheckoutSession'])->name('checkout');
    Route::get('/{order}/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/{order}/checkout/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');

    Route::get('/{order}/download/pdf', [OrderExportController::class, 'pdf'])->name('download.pdf');
    Route::get('/{order}/download/excel', [OrderExportController::class, 'excel'])->name('download.excel');
});

// Stripe webhook (no CSRF)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);
```

---

## File: bootstrap/app.php (add webhook to CSRF exceptions)

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'stripe/webhook',
    ]);
})
```

---

## Continue in Part 2 due to length...

See ORDERS_IMPLEMENTATION_PART2.md for remaining files.
