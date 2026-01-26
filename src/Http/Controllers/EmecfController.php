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
    public function getApiStatus(): JsonResponse
    {
        try {
            $result = $this->emecfService->getStatus();
            
            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null
            ], $result['success'] ? 200 : 400);
        } catch (Exception $e) {
            Log::error('e-MECeF Status Error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération du statut: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les informations du contribuable.
     *
     * @return JsonResponse La réponse JSON.
     */
    public function getTaxpayerInfo(): JsonResponse
    {
        try {
            $result = $this->emecfService->getTaxpayerInfo();
            
            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null
            ], $result['success'] ? 200 : 400);
        } catch (Exception $e) {
            Log::error('e-MECeF Taxpayer Info Error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des informations du contribuable: ' . $e->getMessage()
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
                'type' => 'required|in:' . implode(',', array_keys(EmecfService::INVOICE_TYPES)),
                'reference' => 'required_if:type,FA,EA|string|size:24',
                'items' => 'required|array|min:1',
                'items.*.name' => 'required|string|max:255',
                'items.*.price' => 'required|numeric|min:0',
                'items.*.quantity' => 'required|numeric|min:0',
                'items.*.taxGroup' => 'required|in:' . implode(',', EmecfService::TAX_GROUPS),
                'client' => 'nullable|array',
                'client.ifu' => 'nullable|string|size:13',
                'client.name' => 'nullable|string|max:255',
                'client.contact' => 'nullable|string|max:255',
                'client.address' => 'nullable|string|max:500',
                'operator' => 'required|array',
                'operator.id' => 'nullable|string|max:50',
                'operator.name' => 'required|string|max:255',
                'payment' => 'required|array|min:1',
                'payment.*.name' => 'required|in:' . implode(',', array_keys(EmecfService::PAYMENT_TYPES)),
                'payment.*.amount' => 'required|numeric|min:0'
            ]);

            // Soumettre la facture via le service
            $result = $this->emecfService->submitInvoice($validatedData);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'details' => $result['details'] ?? null
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'invoice_id' => $result['invoice_id'] ?? null
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
            $result = $this->emecfService->finalizeInvoice($uid, $action);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'details' => $result['details'] ?? null
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
     * Obtenir les détails d'une facture.
     *
     * @param string $uid L'identifiant unique de la facture.
     * @return JsonResponse La réponse JSON.
     */
    public function getApiInvoiceDetails(string $uid): JsonResponse
    {
        try {
            $result = $this->emecfService->getInvoiceStatus($uid);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);

        } catch (Exception $e) {
            Log::error('e-MECeF Get API Invoice Details Error', [
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
     * Obtenir les types de factures.
     *
     * @return JsonResponse La réponse JSON.
     */
    public function getInvoiceTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => EmecfService::INVOICE_TYPES
        ]);
    }

    /**
     * Obtenir les types de paiement.
     *
     * @return JsonResponse La réponse JSON.
     */
    public function getPaymentTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => EmecfService::PAYMENT_TYPES
        ]);
    }

    /**
     * Obtenir les groupes de taxation.
     *
     * @return JsonResponse La réponse JSON.
     */
    public function getTaxGroups(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => EmecfService::TAX_GROUPS
        ]);
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
