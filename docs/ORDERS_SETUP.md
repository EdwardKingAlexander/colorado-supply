# Orders Feature - Complete Setup Guide

## Installation Steps

### 1. Install Required Composer Packages

```bash
composer require stripe/stripe-php:^15
composer require barryvdh/laravel-dompdf:^2
composer require maatwebsite/excel:^3.1
```

### 2. Publish Configuration Files

```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

### 3. Environment Variables

Add these to your `.env` file:

```env
# Stripe Configuration
STRIPE_PUBLIC_KEY=pk_test_your_key_here
STRIPE_SECRET_KEY=sk_test_your_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here

# Mail Configuration (if not already set)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@cogovsupply.com"
MAIL_FROM_NAME="Colorado Supply & Procurement LLC"

# Queue Configuration
QUEUE_CONNECTION=database

# App URL (important for Stripe redirects)
APP_URL=https://colorado-supply.test
```

### 4. Run Migrations

```bash
php artisan migrate
```

This will create the following tables:
- `order_sequences`
- `orders`
- `order_items`
- `payments`
- `shipments`
- `stripe_events`

### 5. Register Service Provider

Add to `bootstrap/providers.php`:

```php
App\Providers\StripeServiceProvider::class,
```

### 6. Register Policy

Add to `app/Providers/AppServiceProvider.php` in the `boot()` method:

```php
\Illuminate\Support\Facades\Gate::policy(\App\Models\Order::class, \App\Policies\OrderPolicy::class);
```

### 7. Update Services Config

Add to `config/services.php`:

```php
'stripe' => [
    'public_key' => env('STRIPE_PUBLIC_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],
```

### 8. Add Routes

Routes are already included in the implementation. Verify `routes/web.php` has:

```php
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderExportController;
use App\Http\Controllers\StripeWebhookController;
use App\Models\Order;

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

### 9. Update CSRF Middleware

In `bootstrap/app.php`, add webhook exception:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'stripe/webhook',
    ]);
})
```

### 10. Create Queue Jobs Table (if not exists)

```bash
php artisan queue:table
php artisan migrate
```

### 11. Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## Stripe Webhook Configuration

### Local Development

1. Install Stripe CLI:
   ```bash
   stripe login
   ```

2. Forward webhooks to local app:
   ```bash
   stripe listen --forward-to https://colorado-supply.test/stripe/webhook
   ```

3. Copy the webhook signing secret from the CLI output and add to `.env`:
   ```env
   STRIPE_WEBHOOK_SECRET=whsec_...
   ```

### Production

1. Go to Stripe Dashboard → Developers → Webhooks
2. Click "Add endpoint"
3. Enter your webhook URL: `https://yourdomain.com/stripe/webhook`
4. Select events to listen to:
   - `checkout.session.completed`
   - `payment_intent.payment_failed`
5. Copy the signing secret and add to your production `.env`

---

## Running the Application

### 1. Start Queue Worker

**IMPORTANT:** Emails are queued, so you must run the queue worker:

```bash
php artisan queue:work
```

Or in production, use Supervisor to keep it running.

### 2. Start Development Server (if using artisan serve)

```bash
php artisan serve
```

Or use Laravel Herd (already configured).

---

## How to Test

### Test Checklist

#### 1. Create an Order in Filament

- [ ] Navigate to `/admin`
- [ ] Go to CRM → Orders
- [ ] Click "Create"
- [ ] Fill in:
  - Customer (select existing) OR Cash/Card guest info
  - Billing/Shipping addresses
  - Add order items (with qty, unit price)
  - Observe totals calculate automatically
- [ ] Click "Create"
- [ ] Verify order appears with unique order number (format: `COGOV-YYYYMM-#####`)

#### 2. Test Checkout Flow

- [ ] From the order list, click the "Go to Checkout" action
- [ ] Verify you're redirected to Stripe Checkout page
- [ ] Use Stripe test card: `4242 4242 4242 4242`
  - Expiry: Any future date
  - CVC: Any 3 digits
  - ZIP: Any 5 digits
- [ ] Complete payment
- [ ] Verify redirect back to success page

#### 3. Verify Payment Processing

- [ ] Check order in Filament - payment status should be "Paid"
- [ ] Check your email (customer email) - should receive order confirmation
- [ ] Check `edward@cogovsupply.com` - should also receive copy
- [ ] Verify PDF invoice is attached to email

#### 4. Test PDF/Excel Export

- [ ] From order view, click "Download PDF"
- [ ] Verify PDF downloads with proper formatting
- [ ] Click "Download Excel"
- [ ] Verify Excel file downloads

#### 5. Test Permissions

- [ ] Log in as different roles:
  - Super Admin: Can do everything
  - Admin: Can do everything
  - Sales Manager: Can view/edit all orders
  - Sales Rep: Can only view/edit own orders
  - Viewer: Cannot access orders (should get 403)

#### 6. Test Quote → Order Conversion (Optional)

- [ ] Create a quote in Filament
- [ ] Use the conversion service in Tinker:
  ```php
  $quote = App\Models\Quote::first();
  $service = app(App\Services\Orders\PlaceOrderFromQuote::class);
  $order = $service->execute($quote);
  dd($order->order_number);
  ```

### Stripe Test Cards

- **Success:** `4242 4242 4242 4242`
- **Decline:** `4000 0000 0000 0002`
- **Requires authentication:** `4000 0025 0000 3155`

---

## Manual Testing with Tinker

### Create an Order Programmatically

```php
php artisan tinker

$generator = app(App\Services\Orders\OrderNumberGenerator::class);
$orderNumber = $generator->next();

$order = App\Models\Order::create([
    'order_number' => $orderNumber,
    'customer_id' => 1, // or null for cash/card
    'cash_card_name' => 'John Doe',
    'cash_card_email' => 'john@example.com',
    'contact_name' => 'John Doe',
    'contact_email' => 'john@example.com',
    'status' => App\Enums\OrderStatus::Draft,
    'payment_status' => App\Enums\PaymentStatus::Unpaid,
    'fulfillment_status' => App\Enums\FulfillmentStatus::Unfulfilled,
    'tax_rate' => 8.5,
]);

$order->items()->create([
    'name' => 'Test Product',
    'quantity' => 2,
    'unit_price' => 50.00,
    'line_discount' => 0,
]);

$order->recalcTotals();
$order->save();

echo "Order created: {$order->order_number}\n";
```

### Test Email Sending

```php
$order = App\Models\Order::first();
App\Jobs\SendOrderConfirmationEmail::dispatch($order);
```

Watch the queue worker output to see the email being sent.

### Test Webhook Manually

```bash
curl -X POST https://colorado-supply.test/stripe/webhook \
  -H "Content-Type: application/json" \
  -H "Stripe-Signature: test" \
  -d '{"id":"evt_test","type":"checkout.session.completed"}'
```

Note: This will fail signature verification unless using actual Stripe CLI.

---

## Common Issues & Troubleshooting

### Issue: Emails Not Sending

**Solution:**
- Verify queue worker is running: `php artisan queue:work`
- Check `jobs` table for failed jobs
- Check Laravel logs: `storage/logs/laravel.log`
- Verify mail config in `.env`

### Issue: Stripe Webhooks Not Working

**Solution:**
- Verify webhook secret is correct in `.env`
- Check webhook URL is accessible (no auth middleware)
- Check Stripe dashboard for webhook delivery attempts
- Verify webhook is not cached in route cache: `php artisan route:clear`

### Issue: Order Number Generation Fails

**Solution:**
- Verify `order_sequences` table exists
- Check database connection
- Review error in `storage/logs/laravel.log`

### Issue: PDF Not Generating

**Solution:**
- Verify dompdf is installed: `composer show barryvdh/laravel-dompdf`
- Check file permissions on `storage/` directory
- Clear config cache: `php artisan config:clear`

### Issue: Permission Denied in Filament

**Solution:**
- Verify user has correct role
- Verify `OrderPolicy` is registered in `AppServiceProvider`
- Check gate policy: `Gate::policy(Order::class, OrderPolicy::class);`
- Verify roles exist in database

---

## Customization Guide

### Change Invoice Branding

Edit `resources/views/pdf/invoice.blade.php`:

1. Update company name in header
2. Change colors (search for `#1d4ed8`)
3. Add logo:
   ```html
   <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="height: 50px;">
   ```

### Change Email Template

Edit `resources/views/emails/order-confirmed.blade.php`

### Modify Order Number Format

Edit `app/Services/Orders/OrderNumberGenerator.php`:

```php
// Current format: COGOV-202510-00001
// Change to: INV-202510-00001
return sprintf('INV-%s-%05d', $period, $nextNumber);
```

### Add Custom Order Statuses

1. Edit `app/Enums/OrderStatus.php`
2. Add new case
3. Update label() and color() methods

---

## Production Checklist

- [ ] Set real Stripe keys in production `.env`
- [ ] Configure production webhook in Stripe dashboard
- [ ] Set up Supervisor for queue worker
- [ ] Configure cron for scheduled tasks (if needed)
- [ ] Test email delivery with real email addresses
- [ ] Set up SSL certificate for webhook security
- [ ] Configure backups for `order_sequences` table
- [ ] Set proper file permissions
- [ ] Enable production error logging
- [ ] Test full checkout flow end-to-end
- [ ] Set up monitoring for failed webhooks
- [ ] Configure rate limiting on checkout endpoints

---

## Architecture Notes

### Atomic Order Number Generation

The `OrderNumberGenerator` uses database row-level locking to ensure unique order numbers even under high concurrency:

1. Transaction with row lock (`lockForUpdate()`)
2. Atomic increment
3. Retry logic with exponential backoff
4. Throws exception after max attempts

### Webhook Idempotency

Stripe can send duplicate webhooks. We handle this by:

1. Storing `stripe_event_id` in `stripe_events` table
2. Checking if event already processed before handling
3. Marking event as processed after successful handling

### Email Queueing

All emails are queued for better performance:

1. Order confirmation dispatches `SendOrderConfirmationEmail` job
2. Job sends to customer + edward@cogovsupply.com
3. PDF generated on-the-fly and attached
4. Queue worker processes in background

---

## Support

For issues or questions:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check queue failed jobs: `php artisan queue:failed`
3. Review Stripe webhook logs in dashboard
4. Check this documentation

---

**Implementation Complete!** 🎉

All code is production-ready and follows Laravel 12 best practices.
