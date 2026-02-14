<?php

use Illuminate\Support\Facades\Route;
use LaravelGoogleSheetI18n\Http\Controllers\DashboardController;
use LaravelGoogleSheetI18n\Http\Controllers\TranslationController;
use LaravelGoogleSheetI18n\Http\Controllers\SyncController;
use LaravelGoogleSheetI18n\Http\Middleware\Authorize;

Route::prefix('translation-manager')
    ->middleware(['web', Authorize::class])
    ->group(function () {
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('translation-manager.dashboard');

        // Translations
        Route::get('/translations', [TranslationController::class, 'index'])->name('translation-manager.translations.index');
        Route::get('/translations/{locale}/{file}', [TranslationController::class, 'show'])->name('translation-manager.translations.show');
        Route::put('/translations/{locale}/{file}', [TranslationController::class, 'update'])->name('translation-manager.translations.update');

        // Sync Operations
        Route::post('/sync/upload', [SyncController::class, 'upload'])->name('translation-manager.sync.upload');
        Route::post('/sync/download', [SyncController::class, 'download'])->name('translation-manager.sync.download');
        Route::get('/sync/status', [SyncController::class, 'status'])->name('translation-manager.sync.status');

        // Sheets
        Route::get('/sheets', [SyncController::class, 'sheets'])->name('translation-manager.sheets.index');
    });
