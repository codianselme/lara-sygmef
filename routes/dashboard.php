<?php

use Illuminate\Support\Facades\Route;
use Codianselme\LaraSygmef\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Dashboard e-MECeF Routes
|--------------------------------------------------------------------------
*/

Route::prefix('emecf')->name('emecf.dashboard.')->middleware('web')->group(function () {
    // Dashboard principal
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('index');
    
    // Gestion des factures
    Route::get('/invoices', [DashboardController::class, 'invoices'])->name('invoices');
    Route::get('/invoices/create', [DashboardController::class, 'create'])->name('create');
    
    // Routes POST - Désactiver CSRF pour Testbench
    $csrfMiddleware = app()->environment('testing') ? [] : ['web'];
    
    Route::post('/invoices', [DashboardController::class, 'store'])
        ->name('store')
        ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    
    Route::get('/invoices/{id}', [DashboardController::class, 'show'])
        ->where('id', '[0-9]+')
        ->name('show');
    
    // Actions sur les factures
    Route::post('/invoices/{id}/confirm', [DashboardController::class, 'confirm'])
        ->where('id', '[0-9]+')
        ->name('confirm')
        ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        
    // Route spéciale pour le mode Démo (Confirmation par UID sans ID local)
    Route::post('/invoices/confirm-by-uid', [DashboardController::class, 'confirmByUid'])
        ->name('confirm_by_uid')
        ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        
    Route::post('/invoices/{id}/cancel', [DashboardController::class, 'cancel'])
        ->where('id', '[0-9]+')
        ->name('cancel')
        ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
});
