<?php

namespace Codianselme\LaraSygmef\Http\Controllers;

use Codianselme\LaraSygmef\Services\EmecfService;
use Codianselme\LaraSygmef\Models\EmecfInvoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Validation\ValidationException;

use Illuminate\Routing\Controller;

class EmecfController extends Controller
{
    private EmecfService $emecfService;

    public function __construct(EmecfService $emecfService)
    {
        $this->emecfService = $emecfService;
    }

    /**
     * Obtenir le statut de l'API de facturation
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
     * Soumettre une nouvelle facture
     */
    public function submitInvoice(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
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

            DB::beginTransaction();

            // Soumettre la facture à l'API e-MECeF
            $result = $this->emecfService->submitInvoice($validatedData);

            if (!$result['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'error_code' => $result['error_code'] ?? null,
                    'error_desc' => $result['error_desc'] ?? null
                ], 400);
            }

            // Sauvegarder la facture en base de données
            $invoiceData = $result['data'];
            $invoice = EmecfInvoice::create([
                'uid' => $invoiceData['uid'],
                'ifu' => $validatedData['ifu'],
                'aib' => $validatedData['aib'] ?? null,
                'type' => $validatedData['type'],
                'reference' => $validatedData['reference'] ?? null,
                'operator_id' => $validatedData['operator']['id'] ?? null,
                'operator_name' => $validatedData['operator']['name'],
                'client_ifu' => $validatedData['client']['ifu'] ?? null,
                'client_name' => $validatedData['client']['name'] ?? null,
                'client_contact' => $validatedData['client']['contact'] ?? null,
                'client_address' => $validatedData['client']['address'] ?? null,
                'ta' => $invoiceData['ta'] ?? 0,
                'tb' => $invoiceData['tb'] ?? 0,
                'tc' => $invoiceData['tc'] ?? 0,
                'td' => $invoiceData['td'] ?? 0,
                'taa' => $invoiceData['taa'] ?? 0,
                'tab' => $invoiceData['tab'] ?? 0,
                'tac' => $invoiceData['tac'] ?? 0,
                'tad' => $invoiceData['tad'] ?? 0,
                'tae' => $invoiceData['tae'] ?? 0,
                'taf' => $invoiceData['taf'] ?? 0,
                'hab' => $invoiceData['hab'] ?? 0,
                'had' => $invoiceData['had'] ?? 0,
                'vab' => $invoiceData['vab'] ?? 0,
                'vad' => $invoiceData['vad'] ?? 0,
                'aib_amount' => $invoiceData['aib'] ?? 0,
                'ts' => $invoiceData['ts'] ?? 0,
                'total' => $invoiceData['total'] ?? 0,
                'status' => EmecfInvoice::STATUS_PENDING,
                'submitted_at' => now(),
            ]);

            // Sauvegarder les articles
            foreach ($validatedData['items'] as $item) {
                $invoice->items()->create([
                    'code' => $item['code'] ?? null,
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'tax_group' => $item['taxGroup'],
                    'tax_specific' => $item['taxSpecific'] ?? null,
                    'original_price' => $item['originalPrice'] ?? null,
                    'price_modification' => $item['priceModification'] ?? null,
                ]);
            }

            // Sauvegarder les paiements
            if (isset($validatedData['payment'])) {
                foreach ($validatedData['payment'] as $payment) {
                    $invoice->payments()->create([
                        'name' => $payment['name'],
                        'amount' => $payment['amount'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'invoice_id' => $invoice->id,
                    'uid' => $invoice->uid,
                    'status' => $invoice->status,
                    'total' => $invoice->total,
                    'calculated_amounts' => $invoiceData
                ]
            ], 201);

        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
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
     * Finaliser une facture (confirmer ou annuler)
     */
    public function finalizeInvoice(Request $request, string $uid): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'action' => 'required|in:confirm,cancel'
            ]);

            $invoice = EmecfInvoice::where('uid', $uid)->firstOrFail();

            if ($invoice->status !== EmecfInvoice::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cette facture ne peut plus être finalisée'
                ], 400);
            }

            // Finaliser via l'API e-MECeF
            $result = $this->emecfService->finalizeInvoice($uid, $validatedData['action']);

            if (!$result['success']) {
                $invoice->markAsError(
                    $result['error_code'] ?? 'UNKNOWN',
                    $result['error'] ?? 'Erreur inconnue'
                );

                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'error_code' => $result['error_code'] ?? null,
                    'error_desc' => $result['error_desc'] ?? null
                ], 400);
            }

            // Mettre à jour la facture locale
            if ($validatedData['action'] === 'confirm') {
                $invoice->markAsConfirmed($result['data']);
            } else {
                $invoice->markAsCancelled();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'invoice_id' => $invoice->id,
                    'uid' => $invoice->uid,
                    'status' => $invoice->status,
                    'security_elements' => $result['data'] ?? null
                ]
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
     * Obtenir les détails d'une facture en attente
     */
    public function getPendingInvoiceDetails(string $uid): JsonResponse
    {
        try {
            $invoice = EmecfInvoice::with(['items', 'payments'])
                ->where('uid', $uid)
                ->firstOrFail();

            if ($invoice->status !== EmecfInvoice::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cette facture n\'est pas en attente'
                ], 400);
            }

            // Récupérer les détails depuis l'API e-MECeF
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
                'data' => [
                    'local_invoice' => $invoice,
                    'api_details' => $result['data']
                ]
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
     * Obtenir les informations sur les e-MCF
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
     * Obtenir les groupes de taxation
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
     * Obtenir les types de factures
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
     * Obtenir les types de paiement
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
     * Lister les factures locales
     */
    public function listInvoices(Request $request): JsonResponse
    {
        try {
            $query = EmecfInvoice::with(['items', 'payments']);

            // Filtrage par statut
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filtrage par IFU
            if ($request->has('ifu')) {
                $query->where('ifu', $request->ifu);
            }

            // Filtrage par type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
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
     * Obtenir les détails d'une facture locale
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
