<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\FilterController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\QuoteController;
use App\Http\Controllers\Api\V1\SamOpportunityFavoritesController;
use App\Http\Controllers\Api\V1\SamOpportunityDocumentsController;
use App\Http\Controllers\Api\V1\SamOpportunityRagController;
use App\Http\Controllers\Api\V1\SamOpportunityExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('store')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{slug}', [ProductController::class, 'show']);
        Route::get('/search', [ProductController::class, 'search']);
        Route::get('/filters', FilterController::class);
        Route::post('/quote', [QuoteController::class, 'store']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/sam-opportunities/favorites', [SamOpportunityFavoritesController::class, 'index']);
        Route::post('/sam-opportunities/{samOpportunity}/favorite', [SamOpportunityFavoritesController::class, 'store']);
        Route::delete('/sam-opportunities/{samOpportunity}/favorite', [SamOpportunityFavoritesController::class, 'destroy']);

        Route::get('/sam-opportunities/{samOpportunity}/documents', [SamOpportunityDocumentsController::class, 'index']);
        Route::post('/sam-opportunities/{samOpportunity}/documents', [SamOpportunityDocumentsController::class, 'store']);
        Route::delete('/sam-opportunities/{samOpportunity}/documents/{document}', [SamOpportunityDocumentsController::class, 'destroy']);

        Route::post('/sam-opportunities/{samOpportunity}/rag-query', SamOpportunityRagController::class);

        Route::post('/sam-opportunities/export', [SamOpportunityExportController::class, 'export'])
            ->middleware('throttle:10,60');
    });
});
