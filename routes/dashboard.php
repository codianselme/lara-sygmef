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
    Route::post('/invoices', [DashboardController::class, 'store'])->name('store');
    Route::get('/invoices/{id}', [DashboardController::class, 'show'])->name('show');
    
    // Actions sur les factures
    Route::post('/invoices/{id}/confirm', [DashboardController::class, 'confirm'])->name('confirm');
    Route::post('/invoices/{id}/cancel', [DashboardController::class, 'cancel'])->name('cancel');
});
