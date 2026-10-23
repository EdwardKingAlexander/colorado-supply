<?php

use App\Http\Controllers\Admin\SamOpportunitiesExportController;
use App\Http\Controllers\Auth\MfaSettingsController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Dashboard\LocationController;
use App\Http\Controllers\Dashboard\OrganizationActivityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardReportController;
use App\Http\Controllers\DashboardReportExportController;
use App\Http\Controllers\MilSpecPartsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\RepairServiceController;
use App\Http\Controllers\SmsSubscriptionController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StorePaypalReturnController;
use App\Models\Order;
use App\Models\ScrapedProduct;
use App\Support\StoreAvailability;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Text message program terms (ai/modules/will-call-notifications-complete, phase 6). Public.
Route::view('/sms-terms', 'legal.sms-terms')->name('sms-terms');

Route::post('/privacy/consent', [ConsentController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('privacy.consent.store');

Route::get('/company', [CompanyController::class, 'index'])->middleware(['auth', 'verified.enabled'])->name('company.index');

Route::get('/dashboard', DashboardController::class)->middleware(['auth.web_or_admin', 'verified.enabled'])->name('dashboard');
Route::get('/dashboard/reports', DashboardReportController::class)->middleware(['auth.web_or_admin', 'verified.enabled'])->name('dashboard.reports');
Route::get('/dashboard/reports/export', DashboardReportExportController::class)->middleware(['auth.web_or_admin', 'verified.enabled'])->name('dashboard.reports.export');

Route::middleware(['auth', 'verified.enabled'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
    Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
    Route::patch('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
    Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');
    Route::post('/locations/{location}/restore', [LocationController::class, 'restore'])
        ->withTrashed()
        ->name('locations.restore');
    Route::get('/organization-history', OrganizationActivityController::class)->name('organization-history.index');
});

Route::get('/store', [StoreController::class, 'index'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.index');
Route::get('/store/location/{location:slug}', [StoreController::class, 'index'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.location.index');
Route::get('/store/cart', [StoreController::class, 'cart'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.cart');
Route::get('/store/checkout', [StoreController::class, 'checkout'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.checkout');
Route::get('/store/checkout/{order}/pay', [StoreController::class, 'checkoutPay'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.checkout.pay');
Route::get('/store/checkout/{order}/paypal/return', [StorePaypalReturnController::class, 'return'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.checkout.paypal.return');
// Admin-only preview of the page customers see while the store is closed
// (admins bypass store.enabled, so they can't otherwise see it).
Route::get('/store/maintenance-preview', fn () => Inertia::render('Store/Unavailable', StoreAvailability::maintenancePageProps()))
    ->middleware('auth:admin')
    ->name('store.maintenance-preview');
// Canonical product URL is id-based: slugs are only unique per vendor.
Route::get('/store/product/{product}/{slug?}', [StoreController::class, 'product'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.product');
Route::post('/store/product/{product}/quote-request', [StoreController::class, 'requestQuote'])->middleware(['auth.web_or_admin', 'store.enabled', 'throttle:10,1'])->name('store.product.quote');
// Legacy slug URL: redirects when the slug is unambiguous, otherwise 404.
Route::get('/store/{slug}', [StoreController::class, 'show'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.show');

// Stripe Checkout result pages
Route::get('/store/checkout/{order}/success', function (Order $order) {
    return Inertia::render('Store/CheckoutSuccess', [
        'order' => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status->value,
            'grand_total' => (float) $order->grand_total,
            'pickup' => $order->pickupDetails(),
        ],
    ]);
})->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.checkout.success');

Route::get('/store/checkout/{order}/cancel', function (Order $order) {
    return Inertia::render('Store/CheckoutCancel', [
        'order' => [
            'id' => $order->id,
            'order_number' => $order->order_number,
        ],
    ]);
})->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.checkout.cancel');

// SAM opportunities export (admin only)
Route::get('/admin/sam-opportunities/export', SamOpportunitiesExportController::class)
    ->name('admin.sam-opportunities.export');

// Profile Management
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Two-factor authentication (customer/web guard) — enrollment management.
    Route::post('/profile/two-factor/enable', [MfaSettingsController::class, 'enable'])->name('mfa.enable');
    Route::post('/profile/two-factor/confirm', [MfaSettingsController::class, 'confirm'])->name('mfa.confirm');
    Route::post('/profile/two-factor/recovery-codes', [MfaSettingsController::class, 'regenerateRecoveryCodes'])->name('mfa.recovery-codes');
    Route::delete('/profile/two-factor', [MfaSettingsController::class, 'disable'])->name('mfa.disable');

    // Order-notification bell. Deliberately not verification-gated so an
    // unverified buyer can still receive and follow order updates.
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::middleware('throttle:60,1')->group(function () {
        Route::post('/notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
        Route::post('/notifications/{notification}/unread', [NotificationController::class, 'markUnread'])->name('notifications.mark-unread');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    });
    Route::middleware('throttle:20,1')->group(function () {
        Route::get('/push-subscriptions', [PushSubscriptionController::class, 'index'])->name('push-subscriptions.index');
        Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
        Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
        Route::get('/sms-subscription', [SmsSubscriptionController::class, 'show'])->name('sms-subscription.show');
        Route::post('/sms-subscription', [SmsSubscriptionController::class, 'store'])->name('sms-subscription.store');
        Route::post('/sms-subscription/verify', [SmsSubscriptionController::class, 'verify'])->name('sms-subscription.verify');
        Route::delete('/sms-subscription', [SmsSubscriptionController::class, 'destroy'])->name('sms-subscription.destroy');
    });

    // SAM favorites page
    Route::get('/sam/opportunities/favorites', function () {
        return Inertia::render('Sam/Favorites');
    })->middleware('verified.enabled')->name('sam.favorites');
});

// Public order status tracker: signed URL from order emails — intentionally
// no auth/verification middleware (guests and unverified buyers included).
Route::get('/orders/{order}/track', OrderTrackingController::class)
    ->middleware('signed')
    ->name('orders.track');

// Contact Form Submission
Route::post('/contact',
    [ContactController::class, 'store'])->name('contact.store');

// Repair Services
Route::get('/repair-services', [RepairServiceController::class, 'index'])->name('repair-services.index');
Route::post('/repair-services', [RepairServiceController::class, 'store'])->name('repair-services.store');

// Mil-Spec Parts Database Download
Route::get('/mil-spec-parts/download', [MilSpecPartsController::class, 'downloadExcel'])
    ->middleware(['auth', 'verified.enabled'])
    ->name('mil-spec-parts.download');

// Scraped Product HTML Cache Viewing (admin only)
Route::get('/admin/scraped-products/{scrapedProduct}/html-cache', function (ScrapedProduct $scrapedProduct) {
    if (! auth()->guard('admin')->check()) {
        abort(403);
    }

    if (empty($scrapedProduct->html_cache_path) || ! file_exists($scrapedProduct->html_cache_path)) {
        abort(404, 'HTML cache file not found');
    }

    return response()->file($scrapedProduct->html_cache_path, [
        'Content-Type' => 'text/html',
    ]);
})->name('scraped-products.html-cache');

require __DIR__.'/auth.php';
