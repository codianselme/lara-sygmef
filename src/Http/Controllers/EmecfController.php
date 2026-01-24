<?php

namespace Codianselme\LaraSygmef\Http\Controllers;

use Codianselme\LaraSygmef\Services\EmecfService;
use Codianselme\LaraSygmef\Models\EmecfInvoice;
use Codianselme\LaraSygmef\Enums\InvoiceStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Controller;

/**
 * Contrôleur pour gérer les requêtes e-MECeF.
 */
class EmecfController extends Controller
{
    /**
     * @var EmecfService Le service e-MECeF.
     */
    private EmecfService $emecfService;

    /**
     * Constructeur du contrôleur.
     *
     * @param EmecfService $emecfService Le service e-MECeF.
     */
    public function __construct(EmecfService $emecfService)
    {
        $this->emecfService = $emecfService;
    }

    /**
     * Obtenir le statut de l'API de facturation.
     *
     * @return JsonResponse La réponse JSON.
     */
    public function getInvoiceStatus(): JsonResponse
    {
        try {
            $result = $this->emecfService->getInvoiceStatus();
            
            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null,
                'error_code' => $result['error_code'] ?? null
            ], $result['success'] ? 200 : 400);
        } catch (Exception $e) {
            Log::error('e-MECeF Status Error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération du statut'
            ], 500);
        }
    }

    /**
     * Soumettre une nouvelle facture.
     *
     * @param Request $request La requête HTTP.
     * @return JsonResponse La réponse JSON.
     * @throws ValidationException Si la validation échoue.
     */
    public function submitInvoice(Request $request): JsonResponse
    {
        try {
            $validatedData = (array) $request->validate([
                'ifu' => 'required|string|size:13',
                'aib' => 'nullable|in:A,B',
                'type' => 'required|in:FV,EV,FA,EA',
                'reference' => 'required_if:type,FA,EA|string|size:24',
                'items' => 'required|array|min:1',
                'items.*.code' => 'nullable|string',
                'items.*.name' => 'required|string|max:255',
                'items.*.price' => 'required|integer|min:0',
                'items.*.quantity' => 'required|numeric|min:0',
                'items.*.taxGroup' => 'required|in:A,B,C,D,E,F',
                'items.*.taxSpecific' => 'nullable|integer|min:0',
                'items.*.originalPrice' => 'nullable|integer|min:0',
                'items.*.priceModification' => 'nullable|string|max:255',
                'client' => 'nullable|array',
                'client.ifu' => 'nullable|string|size:13',
                'client.name' => 'nullable|string|max:255',
                'client.contact' => 'nullable|string|max:255',
                'client.address' => 'nullable|string|max:500',
                'operator' => 'required|array',
                'operator.id' => 'nullable|string|max:50',
                'operator.name' => 'required|string|max:255',
                'payment' => 'nullable|array',
                'payment.*.name' => 'required|in:ESPECES,VIREMENT,CARTEBANCAIRE,MOBILEMONEY,CHEQUES,CREDIT,AUTRE',
                'payment.*.amount' => 'required|integer|min:0'
            ]);

            // Soumettre la facture via le service
            // Le service gère désormais la sauvegarde automatique si configuré
            $result = $this->emecfService->submitInvoice($validatedData);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'error_code' => $result['error_code'] ?? null,
                    'error_desc' => $result['error_desc'] ?? null
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ], 201);

        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('e-MECeF Submit Invoice Error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la soumission de la facture: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finaliser une facture (confirmer ou annuler).
     *
     * @param Request $request La requête HTTP.
     * @param string $uid L'identifiant unique de la facture.
     * @return JsonResponse La réponse JSON.
     * @throws ValidationException Si la validation échoue.
     */
    public function finalizeInvoice(Request $request, string $uid): JsonResponse
    {
        try {
            $validatedData = (array) $request->validate([
                'action' => 'required|in:confirm,cancel'
            ]);

            /** @var string $action */
            $action = $validatedData['action'];

            // Finaliser via le service
            // Le service gère désormais la mise à jour automatique de la facture locale si configuré
            $result = $this->emecfService->finalizeInvoice($uid, $action);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'error_code' => $result['error_code'] ?? null,
                    'error_desc' => $result['error_desc'] ?? null
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);

        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('e-MECeF Finalize Invoice Error', [
                'error' => $e->getMessage(),
                'uid' => $uid
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la finalisation de la facture: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les détails d'une facture en attente.
     *
     * @param string $uid L'identifiant unique de la facture.
     * @return JsonResponse La réponse JSON.
     */
    public function getPendingInvoiceDetails(string $uid): JsonResponse
    {
        try {
            // Récupérer les détails depuis l'API e-MECeF via le service
            $result = $this->emecfService->getPendingInvoiceDetails($uid);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'error_code' => $result['error_code'] ?? null
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);

        } catch (Exception $e) {
            Log::error('e-MECeF Get Invoice Details Error', [
                'error' => $e->getMessage(),
                'uid' => $uid
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des détails de la facture: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les informations sur les e-MCF.
     *
     * @return JsonResponse La réponse JSON.
     */
    public function getEmcfInfo(): JsonResponse
    {
        try {
            $result = $this->emecfService->getEmcfInfo();
            
            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null,
                'error_code' => $result['error_code'] ?? null
            ], $result['success'] ? 200 : 400);
        } catch (Exception $e) {
            Log::error('e-MECeF Info Error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des informations e-MCF'
            ], 500);
        }
    }

    /**
     * Obtenir les groupes de taxation.
     *
     * @return JsonResponse La réponse JSON.
     */
    public function getTaxGroups(): JsonResponse
    {
        try {
            $result = $this->emecfService->getTaxGroups();
            
            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null,
                'error_code' => $result['error_code'] ?? null
            ], $result['success'] ? 200 : 400);
        } catch (Exception $e) {
            Log::error('e-MECeF Tax Groups Error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des groupes de taxation'
            ], 500);
        }
    }

    /**
     * Obtenir les types de factures.
     *
     * @return JsonResponse La réponse JSON.
     */
    public function getInvoiceTypes(): JsonResponse
    {
        try {
            $result = $this->emecfService->getInvoiceTypes();
            
            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null,
                'error_code' => $result['error_code'] ?? null
            ], $result['success'] ? 200 : 400);
        } catch (Exception $e) {
            Log::error('e-MECeF Invoice Types Error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des types de factures'
            ], 500);
        }
    }

    /**
     * Obtenir les types de paiement.
     *
     * @return JsonResponse La réponse JSON.
     */
    public function getPaymentTypes(): JsonResponse
    {
        try {
            $result = $this->emecfService->getPaymentTypes();
            
            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null,
                'error_code' => $result['error_code'] ?? null
            ], $result['success'] ? 200 : 400);
        } catch (Exception $e) {
            Log::error('e-MECeF Payment Types Error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des types de paiement'
            ], 500);
        }
    }

    /**
     * Lister les factures locales.
     *
     * @param Request $request La requête HTTP.
     * @return JsonResponse La réponse JSON.
     */
    public function listInvoices(Request $request): JsonResponse
    {
        try {
            $query = EmecfInvoice::with(['items', 'payments']);

            // Filtrage par statut
            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            // Filtrage par IFU
            if ($request->has('ifu')) {
                $query->where('ifu', $request->get('ifu'));
            }

            // Filtrage par type
            if ($request->has('type')) {
                $query->where('type', $request->get('type'));
            }

            // Pagination
            $perPage = (int) $request->get('per_page', 15);
            $invoices = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $invoices
            ]);

        } catch (Exception $e) {
            Log::error('e-MECeF List Invoices Error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération de la liste des factures'
            ], 500);
        }
    }

    /**
     * Obtenir les détails d'une facture locale.
     *
     * @param int $id L'identifiant de la facture.
     * @return JsonResponse La réponse JSON.
     */
    public function getInvoiceDetails(int $id): JsonResponse
    {
        try {
            $invoice = EmecfInvoice::with(['items', 'payments'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);

        } catch (Exception $e) {
            Log::error('e-MECeF Get Invoice Details Error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Facture non trouvée'
            ], 404);
        }
    }
}
