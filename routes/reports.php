<?php

use App\Http\Controllers\Reports\ReportCatalogController;
use App\Http\Controllers\Reports\ReportPreviewController;
use App\Http\Controllers\Reports\ReportTemplateController;
use App\Http\Controllers\Reports\SavedReportController;
use Illuminate\Support\Facades\Route;

/*
| Report builder JSON API (ai/modules/report-builder, phase 3). Portal users
| only; every run applies the requesting user's tenant scope. The builder
| pages themselves arrive in phase 4.
*/
Route::middleware(['auth:web', 'verified.enabled'])
    ->prefix('account/reports')
    ->name('dashboard.reports.')
    ->group(function () {
        Route::get('/catalog', ReportCatalogController::class)->name('catalog');
        Route::get('/templates', ReportTemplateController::class)->name('templates');
        Route::post('/preview', ReportPreviewController::class)->middleware('throttle:60,1')->name('preview');

        Route::get('/saved', [SavedReportController::class, 'index'])->name('saved.index');
        Route::post('/saved', [SavedReportController::class, 'store'])->middleware('throttle:30,1')->name('saved.store');
        Route::get('/saved/{report}', [SavedReportController::class, 'show'])->name('saved.show');
        Route::patch('/saved/{report}', [SavedReportController::class, 'update'])->middleware('throttle:60,1')->name('saved.update');
        Route::delete('/saved/{report}', [SavedReportController::class, 'destroy'])->name('saved.destroy');
        Route::post('/saved/{report}/duplicate', [SavedReportController::class, 'duplicate'])->middleware('throttle:30,1')->name('saved.duplicate');
        Route::post('/saved/{report}/run', [SavedReportController::class, 'run'])->middleware('throttle:60,1')->name('saved.run');
    });
