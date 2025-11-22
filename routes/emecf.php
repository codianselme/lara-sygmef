<?php

// Routes e-MECeF - À inclure dans routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Codianselme\LaraSygmef\Http\Controllers\EmecfController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| e-MECeF API Routes
|--------------------------------------------------------------------------
|
| Routes pour l'API e-MECeF (Module de contrôle dématérialisé)
| Ces routes permettent de gérer les factures normalisées conformément
| aux spécifications de la DGI du Bénin
|
*/

// Route pour le statut de l'API de facturation
Route::get('/emecf/status', [EmecfController::class, 'getInvoiceStatus']);

// Routes pour les factures
Route::prefix('/emecf/invoices')->group(function () {
    // Soumettre une nouvelle facture
    Route::post('/', [EmecfController::class, 'submitInvoice']);
    
    // Finaliser une facture (confirmer/annuler)
    Route::put('/{uid}/finalize', [EmecfController::class, 'finalizeInvoice']);
    
    // Obtenir les détails d'une facture en attente
    Route::get('/{uid}/pending', [EmecfController::class, 'getPendingInvoiceDetails']);
    
    // Lister les factures locales
    Route::get('/', [EmecfController::class, 'listInvoices']);
    
    // Obtenir les détails d'une facture locale
    Route::get('/{id}', [EmecfController::class, 'getInvoiceDetails']);
});

// Routes pour les informations e-MECeF
Route::prefix('/emecf/info')->group(function () {
    // Informations sur les e-MCF
    Route::get('/emcf', [EmecfController::class, 'getEmcfInfo']);
    
    // Groupes de taxation
    Route::get('/tax-groups', [EmecfController::class, 'getTaxGroups']);
    
    // Types de factures
    Route::get('/invoice-types', [EmecfController::class, 'getInvoiceTypes']);
    
    // Types de paiement
    Route::get('/payment-types', [EmecfController::class, 'getPaymentTypes']);
});

/*
|--------------------------------------------------------------------------
| Routes alternatives pour compatibilité avec l'API e-MECeF
|--------------------------------------------------------------------------
| Ces routes maintiennent la compatibilité avec la structure de l'API
| officielle de la DGI tout en ajoutant une couche de gestion locale
|
*/

// Route de statut compatible avec l'API officielle
Route::get('/emecf/api/invoice', [EmecfController::class, 'getInvoiceStatus']);

// Routes de facturation compatibles
Route::prefix('/emecf/api/invoice')->group(function () {
    // Soumettre une facture
    Route::post('/', [EmecfController::class, 'submitInvoice']);
    
    // Finaliser une facture
    Route::put('/{uid}/{action}', function (Request $request, string $uid, string $action) {
        $request->merge(['action' => $action]);
        return app(EmecfController::class)->finalizeInvoice($request, $uid);
    });
    
    // Détails d'une facture en attente
    Route::get('/{uid}', [EmecfController::class, 'getPendingInvoiceDetails']);
});

// Routes d'information compatibles
Route::prefix('/emecf/api/info')->group(function () {
    // Statut e-MCF
    Route::get('/status', [EmecfController::class, 'getEmcfInfo']);
    
    // Groupes de taxation
    Route::get('/taxGroups', [EmecfController::class, 'getTaxGroups']);
    
    // Types de factures
    Route::get('/invoiceTypes', [EmecfController::class, 'getInvoiceTypes']);
    
    // Types de paiement
    Route::get('/paymentTypes', [EmecfController::class, 'getPaymentTypes']);
});
