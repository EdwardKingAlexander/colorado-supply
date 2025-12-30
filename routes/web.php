<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\MilSpecPartsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoreController;
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

Route::get('/company', [\App\Http\Controllers\CompanyController::class, 'index'])->middleware(['auth'])->name('company.index');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/store', [StoreController::class, 'index'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.index');
Route::get('/store/location/{location:slug}', [StoreController::class, 'index'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.location.index');
Route::get('/store/quote', [StoreController::class, 'quote'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.quote');
Route::get('/store/{slug}', [StoreController::class, 'show'])->middleware(['auth.web_or_admin', 'store.enabled'])->name('store.show');

// Profile Management
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // SAM favorites page
    Route::get('/sam/opportunities/favorites', function () {
        return Inertia::render('Sam/Favorites');
    })->name('sam.favorites');
});

// Contact Form Submission
Route::post('/contact',
    [ContactController::class, 'store'])->name('contact.store');

// Mil-Spec Parts Database Download
Route::get('/mil-spec-parts/download', [MilSpecPartsController::class, 'downloadExcel'])
    ->middleware('auth')
    ->name('mil-spec-parts.download');

// Scraped Product HTML Cache Viewing (admin only)
Route::get('/admin/scraped-products/{scrapedProduct}/html-cache', function (\App\Models\ScrapedProduct $scrapedProduct) {
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
