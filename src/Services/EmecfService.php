<?php

namespace Codianselme\LaraSygmef\Services;

use Codianselme\LaraSygmef\Enums\InvoiceStatus;
use Codianselme\LaraSygmef\Models\EmecfInvoice;
use Codianselme\LaraSygmef\Models\EmecfInvoiceItem;
use Codianselme\LaraSygmef\Models\EmecfInvoicePayment;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;

/**
 * Service pour interagir avec l'API e-MECeF de la DGI Bénin.
 */
class EmecfService
{
    /** @var Client Le client HTTP Guzzle */
    private Client $client;

    /** @var string L'URL de base de l'API */
    private string $baseUrl;

    /** @var bool Si les factures doivent être sauvegardées localement */
    private bool $shouldSaveInvoices;

    /** @var array<string, string> Liste des types de factures valides */
    private const INVOICE_TYPES = [
        'FV' => 'Facture de Vente',
        'EV' => 'Facture d\'Exportation',
        'FA' => 'Facture d\'Avoir',
        'EA' => 'Facture d\'Avoir d\'Exportation',
    ];

    /**
     * Constructeur de EmecfService.
     */
    public function __construct()
    {
        $this->baseUrl = config('emecf.mode') === 'production'
            ? config('emecf.urls.production')
            : config('emecf.urls.test');

        $this->shouldSaveInvoices = (bool) config('emecf.save_invoices', false);

        $this->client = new Client([
            'base_uri' => $this->baseUrl . '/',
            'headers' => [
                'Authorization' => 'Bearer ' . config('emecf.token'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'verify' => false, // Désactiver la vérification SSL si nécessaire pour le test
        ]);
    }

    /**
     * Récupérer le statut de l'API.
     *
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        try {
            $response = $this->client->get('status');
            return [
                'success' => true,
                'data' => json_decode($response->getBody()->getContents(), true)
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Récupérer les informations de l'utilisateur.
     *
     * @return array<string, mixed>
     */
    public function getUserInfo(): array
    {
        try {
            $response = $this->client->get('user-info');
            return [
                'success' => true,
                'data' => json_decode($response->getBody()->getContents(), true)
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Soumettre une facture pour normalisation.
     *
     * @param array<string, mixed> $data Les données de la facture.
     * @return array<string, mixed>
     */
    public function submitInvoice(array $data): array
    {
        try {
            $validatedData = $this->validateInvoiceData($data);
            $response = $this->client->post('invoice', [
                'json' => $validatedData
            ]);
            $responseData = (array) json_decode($response->getBody()->getContents(), true);

            // Sauvegarde locale si activée
            $localInvoice = null;
            if ($this->shouldSaveInvoices) {
                $localInvoice = $this->saveInvoiceLocally($data, $responseData);
            }

            return [
                'success' => true,
                'data' => $responseData,
                'invoice_id' => $localInvoice ? $localInvoice->id : null
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Finaliser une facture (confirmer ou annuler).
     *
     * @param string $uid L'identifiant unique de la facture.
     * @param string $action L'action à effectuer (confirm ou cancel).
     * @return array<string, mixed>
     * @throws \Exception Si l'action est invalide.
     */
    public function finalizeInvoice(string $uid, string $action = 'confirm'): array
    {
        if (!in_array($action, ['confirm', 'cancel'])) {
            throw new \Exception('L\'action doit être "confirm" ou "cancel"');
        }

        try {
            $response = $this->client->put("invoice/" . $uid . "/" . $action);
            $responseData = (array) json_decode($response->getBody()->getContents(), true);

            // Mise à jour locale si activée
            if ($this->shouldSaveInvoices) {
                $this->updateLocalInvoiceAfterFinalization($uid, $responseData, $action);
            }

            return [
                'success' => true,
                'data' => $responseData
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer les détails d'une facture.
     *
     * @param string $uid L'identifiant unique de la facture.
     * @return array<string, mixed>
     */
    public function getInvoiceDetails(string $uid): array
    {
        try {
            $response = $this->client->get("invoice/" . $uid);
            return [
                'success' => true,
                'data' => json_decode($response->getBody()->getContents(), true)
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Gérer les erreurs de requête.
     *
     * @param RequestException $e L'exception de requête.
     * @return array<string, mixed>
     */
    private function handleError(RequestException $e): array
    {
        $response = $e->getResponse();
        $statusCode = $response ? $response->getStatusCode() : 500;
        $content = $response ? json_decode($response->getBody()->getContents(), true) : ['error' => 'Erreur de connexion à l\'API'];

        return [
            'success' => false,
            'status' => $statusCode,
            'error' => $content['message'] ?? $content['error'] ?? 'Une erreur inconnue est survenue'
        ];
    }

    /**
     * Sauvegarder une facture localement.
     *
     * @param array<string, mixed> $requestData Les données envoyées à l'API.
     * @param array<string, mixed> $responseData La réponse reçue de l'API.
     * @return EmecfInvoice
     */
    private function saveInvoiceLocally(array $requestData, array $responseData): EmecfInvoice
    {
        return DB::transaction(function () use ($requestData, $responseData) {
            /** @var EmecfInvoice $invoice */
            $invoice = EmecfInvoice::create([
                'uid' => $responseData['uid'],
                'ifu' => $requestData['ifu'],
                'aib' => $requestData['aib'] ?? null,
                'type' => $requestData['type'],
                'reference' => $requestData['reference'] ?? null,
                'operator_id' => $requestData['operator']['id'] ?? null,
                'operator_name' => $requestData['operator']['name'],
                'client_ifu' => $requestData['client']['ifu'] ?? null,
                'client_name' => $requestData['client']['name'] ?? null,
                'client_contact' => $requestData['client']['contact'] ?? null,
                'client_address' => $requestData['client']['address'] ?? null,
                'status' => InvoiceStatus::PENDING,
                'ta' => $responseData['ta'] ?? 0,
                'tb' => $responseData['tb'] ?? 0,
                'tc' => $responseData['tc'] ?? 0,
                'td' => $responseData['td'] ?? 0,
                'taa' => $responseData['taa'] ?? 0,
                'tab' => $responseData['tab'] ?? 0,
                'tac' => $responseData['tac'] ?? 0,
                'tad' => $responseData['tad'] ?? 0,
                'tae' => $responseData['tae'] ?? 0,
                'taf' => $responseData['taf'] ?? 0,
                'hab' => $responseData['hab'] ?? 0,
                'had' => $responseData['had'] ?? 0,
                'vab' => $responseData['vab'] ?? 0,
                'vad' => $responseData['vad'] ?? 0,
                'aib_amount' => $responseData['aib'] ?? 0,
                'ts' => $responseData['ts'] ?? 0,
                'total' => $responseData['total'] ?? 0,
                'submitted_at' => now(),
            ]);

            if (isset($requestData['items'])) {
                foreach ($requestData['items'] as $item) {
                    EmecfInvoiceItem::create([
                        'emecf_invoice_id' => $invoice->id,
                        'name' => $item['name'],
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'tax_group' => $item['taxGroup'],
                    ]);
                }
            }

            if (isset($requestData['payment'])) {
                foreach ($requestData['payment'] as $payment) {
                    EmecfInvoicePayment::create([
                        'emecf_invoice_id' => $invoice->id,
                        'name' => $payment['name'],
                        'amount' => $payment['amount'],
                    ]);
                }
            }

            return $invoice;
        });
    }

    /**
     * Mettre à jour une facture locale après finalisation.
     *
     * @param string $uid L'identifiant unique de la facture.
     * @param array<string, mixed> $responseData La réponse de finalisation de l'API.
     * @param string $action L'action effectuée (confirm ou cancel).
     * @return void
     */
    private function updateLocalInvoiceAfterFinalization(string $uid, array $responseData, string $action = 'confirm'): void
    {
        $invoice = EmecfInvoice::where('uid', $uid)->first();

        if ($invoice) {
            $updateData = [
                'status' => $action === 'confirm' ? InvoiceStatus::CONFIRMED : InvoiceStatus::CANCELLED,
                'finalized_at' => now(),
            ];

            if ($action === 'confirm') {
                $updateData['qr_code'] = $responseData['qrCode'] ?? null;
                $updateData['code_mec_ef_dgi'] = $responseData['codeMECeFDGI'] ?? null;
                $updateData['date_time'] = $responseData['dateTime'] ?? null;
                $updateData['counters'] = $responseData['counters'] ?? null;
                $updateData['nim'] = $responseData['nim'] ?? null;
            }

            $invoice->update($updateData);
        }
    }

    /**
     * Valider les données de facture.
     *
     * @param array<string, mixed> $data Les données à valider.
     * @return array<string, mixed> Les données validées.
     * @throws \Exception Si les données sont invalides.
     */
    private function validateInvoiceData(array $data): array
    {
        // Validation des champs obligatoires
        if (empty($data['ifu'])) {
            throw new \Exception('L\'IFU est obligatoire');
        }

        if (empty($data['type']) || !array_key_exists($data['type'], self::INVOICE_TYPES)) {
            throw new \Exception('Le type de facture est invalide');
        }

        if (empty($data['items']) || !is_array($data['items'])) {
            throw new \Exception('La facture doit contenir au moins un article');
        }

        // Validation pour les factures d'avoir
        if (in_array($data['type'], ['FA', 'EA']) && empty($data['reference'])) {
            throw new \Exception('La référence de la facture originale est obligatoire pour les factures d\'avoir');
        }

        // La validation de longueur est laissée à l'API car l'UID peut varier (24 ou 36 chars)
        if (in_array($data['type'], ['FA', 'EA']) && isset($data['reference']) && strlen((string)$data['reference']) !== 24) {
           throw new \Exception('La référence de la facture originale doit contenir 24 caractères');
        }

        foreach ($data['items'] as $index => $item) {
            if (empty($item['name'])) {
                throw new \Exception('Le nom de l\'article à l\'index ' . $index . ' est obligatoire');
            }
            if (!isset($item['price']) || $item['price'] < 0) {
                throw new \Exception('Le prix de l\'article à l\'index ' . $index . ' est invalide');
            }
            if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                throw new \Exception('La quantité de l\'article à l\'index ' . $index . ' doit être supérieure à 0');
            }
            if (empty($item['taxGroup'])) {
                throw new \Exception('Le groupe de taxation de l\'article à l\'index ' . $index . ' est obligatoire');
            }
        }

        if (empty($data['operator']['name'])) {
            throw new \Exception('Le nom de l\'opérateur est obligatoire');
        }

        if (empty($data['payment']) || !is_array($data['payment'])) {
            throw new \Exception('Au moins un mode de paiement est obligatoire');
        }

        foreach ($data['payment'] as $index => $payment) {
            if (empty($payment['name'])) {
                throw new \Exception('Le nom du mode de paiement à l\'index ' . $index . ' est obligatoire');
            }
            if (!isset($payment['amount']) || $payment['amount'] < 0) {
                throw new \Exception('Le montant du paiement à l\'index ' . $index . ' est invalide');
            }
        }

        return $data;
    }
}
