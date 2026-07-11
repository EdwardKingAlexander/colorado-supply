<?php

use App\Http\Controllers\Admin\SamOpportunitiesExportController;
use App\Http\Controllers\Auth\MfaSettingsController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardReportController;
use App\Http\Controllers\DashboardReportExportController;
use App\Http\Controllers\MilSpecPartsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RepairServiceController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StorePaypalReturnController;
use App\Models\Order;
use App\Models\ScrapedProduct;
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

Route::post('/privacy/consent', [ConsentController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('privacy.consent.store');

Route::get('/company', [CompanyController::class, 'index'])->middleware(['auth', 'verified.enabled'])->name('company.index');

Route::get('/dashboard', DashboardController::class)->middleware(['auth.web_or_admin', 'verified.enabled'])->name('dashboard');
Route::get('/dashboard/reports', DashboardReportController::class)->middleware(['auth.web_or_admin', 'verified.enabled'])->name('dashboard.reports');
Route::get('/dashboard/reports/export', DashboardReportExportController::class)->middleware(['auth.web_or_admin', 'verified.enabled'])->name('dashboard.reports.export');

Route::get('/store', [StoreController::class, 'index'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.index');
Route::get('/store/location/{location:slug}', [StoreController::class, 'index'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.location.index');
Route::get('/store/cart', [StoreController::class, 'cart'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.cart');
Route::get('/store/checkout', [StoreController::class, 'checkout'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.checkout');
Route::get('/store/checkout/{order}/pay', [StoreController::class, 'checkoutPay'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.checkout.pay');
Route::get('/store/checkout/{order}/paypal/return', [StorePaypalReturnController::class, 'return'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.checkout.paypal.return');
Route::get('/store/{slug}', [StoreController::class, 'show'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.show');

// Stripe Checkout result pages
Route::get('/store/checkout/{order}/success', function (Order $order) {
    return Inertia::render('Store/CheckoutSuccess', [
        'order' => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status->value,
            'grand_total' => (float) $order->grand_total,
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
    Route::post('/notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read');

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
