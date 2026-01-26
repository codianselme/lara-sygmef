<?php

namespace Codianselme\LaraSygmef\Services;

use Codianselme\LaraSygmef\Models\EmecfInvoice;
use Codianselme\LaraSygmef\Models\EmecfInvoiceItem;
use Codianselme\LaraSygmef\Models\EmecfInvoicePayment;
use Codianselme\LaraSygmef\Enums\InvoiceStatus;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Service pour interagir avec l'API e-MECeF de la DGI Bénin.
 */
class EmecfService
{
    /**
     * @var Client Le client HTTP Guzzle.
     */
    private Client $client;

    /**
     * @var string L'URL de base de l'API.
     */
    private string $baseUrl;

    /**
     * @var string Le jeton d'authentification.
     */
    private string $token;

    /**
     * @var bool Indique si le service est en mode test.
     */
    private bool $isTestMode;

    /**
     * @var bool Indique si les factures doivent être sauvegardées localement.
     */
    private bool $shouldSaveInvoices;

    /**
     * Types de factures disponibles.
     */
    public const INVOICE_TYPES = [
        'FV' => 'Facture de vente',
        'EV' => 'Facture de vente à l\'exportation',
        'FA' => 'Facture d\'avoir',
        'EA' => 'Facture d\'avoir à l\'exportation'
    ];

    /**
     * Types de paiement disponibles.
     */
    public const PAYMENT_TYPES = [
        'ESPECES' => 'Espèces',
        'VIREMENT' => 'Virement',
        'CARTE_BANCAIRE' => 'Carte bancaire',
        'MOBILE_MONEY' => 'Mobile Money',
        'CHEQUE' => 'Chèque',
        'CREDIT' => 'Crédit',
        'AUTRE' => 'Autre'
    ];

    /**
     * Groupes de taxation DGI.
     */
    public const TAX_GROUPS = [
        'A', 'B', 'C', 'D', 'E', 'F'
    ];

    /**
     * Codes d'erreur courants de l'API e-MECeF.
     */
    public const ERROR_CODES = [
        1 => 'Le jeton d\'authentification est invalide ou expiré',
        2 => 'L\'IFU n\'est pas valide',
        3 => 'Type de facture n\'est pas valide',
        4 => 'Le format de la requête n\'est pas valide',
        5 => 'L\'identifiant de l\'opérateur n\'est pas valide',
        6 => 'La valeur de l\'AIB n\'est pas valide',
        7 => 'Le type de paiement n\'est pas valide',
        8 => 'Le montant du paiement n\'est pas valide',
        9 => 'Le groupe de taxation au niveau des articles n\'est pas valide',
        10 => 'Le prix unitaire de l\'article n\'est pas valide',
        11 => 'La référence de la facture originale n\'est pas valide (la facture originale est introuvable)',
        12 => 'La référence de la facture originale n\'est pas valide (le montant sur la facture d\'avoir dépassé le montant de la facture originale)',
        13 => 'Le montant total de la facture est invalide',
        20 => 'La facture n\'existe pas ou elle est déjà finalisée / annulée',
    ];

    /**
     * Créer une nouvelle instance de EmecfService.
     *
     * @throws \Exception Si le token e-MECeF n'est pas configuré.
     */
    public function __construct()
    {
        $this->isTestMode = (bool) config('emecf.test_mode', true);
        $this->baseUrl = $this->isTestMode 
            ? config('emecf.urls.test.invoice') 
            : config('emecf.urls.production.invoice');
        
        $this->shouldSaveInvoices = (bool) config('emecf.database.save_invoices', true);
        $this->token = (string) config('emecf.token');

        if (empty($this->token)) {
            throw new \Exception('Token e-MECeF non configuré');
        }

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
            ],
            'verify' => false, // Désactiver la vérification SSL pour le test
        ]);
    }

    /**
     * Envoyer une facture à l'API e-MECeF.
     *
     * @param array<string, mixed> $data Les données de la facture.
     * @return array<string, mixed> La réponse de l'API.
     * @throws \Exception Si les données sont invalides ou si la requête échoue.
     */
    public function submitInvoice(array $data): array
    {
        $data = $this->validateInvoiceData($data);

        try {
            $response = $this->client->post('invoice', [
                'json' => $data
            ]);

            $responseData = (array) json_decode($response->getBody()->getContents(), true);

            // Sauvegarde locale si activée
            if ($this->shouldSaveInvoices && isset($responseData['uid'])) {
                $this->saveInvoiceLocally($data, $responseData);
            }

            return [
                'success' => true,
                'data' => $responseData
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Finaliser ou annuler une facture.
     *
     * @param string $uid L'identifiant unique de la facture.
     * @param string $action L'action à effectuer (confirm ou cancel).
     * @return array<string, mixed> La réponse de l'API.
     * @throws \Exception Si l'action est invalide.
     */
    public function finalizeInvoice(string $uid, string $action = 'confirm'): array
    {
        if (!in_array($action, ['confirm', 'cancel'])) {
            throw new \Exception('L\'action doit être "confirm" ou "cancel"');
        }

        try {
            $response = $this->client->put("invoice/{$uid}/{$action}");
            $responseData = (array) json_decode($response->getBody()->getContents(), true);

            // Mise à jour locale si activée
            if ($this->shouldSaveInvoices && $action === 'confirm') {
                $this->updateLocalInvoiceAfterFinalization($uid, $responseData);
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
     * Obtenir le statut d'une facture.
     *
     * @param string $uid L'identifiant unique de la facture.
     * @return array<string, mixed> La réponse de l'API.
     */
    public function getInvoiceStatus(string $uid): array
    {
        try {
            $response = $this->client->get("invoice/{$uid}");
            $data = (array) json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'data' => $data
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Obtenir les informations du contribuable.
     *
     * @return array<string, mixed> La réponse de l'API.
     */
    public function getTaxpayerInfo(): array
    {
        try {
            $response = $this->client->get('info');
            $data = (array) json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'data' => $data
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Obtenir les statistiques du contribuable.
     *
     * @return array<string, mixed> La réponse de l'API.
     */
    public function getTaxpayerStats(): array
    {
        try {
            $response = $this->client->get('stats');
            $data = (array) json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'data' => $data
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Obtenir la liste des factures.
     *
     * @param int $page Le numéro de page.
     * @return array<string, mixed> La réponse de l'API.
     */
    public function getInvoices(int $page = 1): array
    {
        try {
            $response = $this->client->get('invoices', [
                'query' => ['page' => $page]
            ]);
            $data = (array) json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'data' => $data
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Enregistrer une facture localement.
     *
     * @param array<string, mixed> $requestData Les données de la requête originale.
     * @param array<string, mixed> $responseData La réponse de l'API e-MECeF.
     * @return EmecfInvoice La facture enregistrée.
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
                'total' => $responseData['total'] ?? 0,
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
                        'type' => $payment['name'],
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
     * @return void
     */
    private function updateLocalInvoiceAfterFinalization(string $uid, array $responseData): void
    {
        $invoice = EmecfInvoice::where('uid', $uid)->first();

        if ($invoice) {
            $invoice->update([
                'status' => InvoiceStatus::FINALIZED,
                'qr_code' => $responseData['qrCode'] ?? null,
                'counters' => $responseData['counters'] ?? null,
                'signature' => $responseData['signature'] ?? null,
                'finalized_at' => now(),
            ]);
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

        // Validation des articles
        foreach ($data['items'] as $index => $item) {
            if (empty($item['name'])) {
                throw new \Exception("Le nom de l'article à l'index " . $index . " est obligatoire");
            }

            if (!isset($item['price']) || !is_numeric($item['price'])) {
                throw new \Exception("Le prix de l'article à l'index " . $index . " est invalide");
            }

            if (!isset($item['quantity']) || !is_numeric($item['quantity'])) {
                throw new \Exception("La quantité de l'article à l'index " . $index . " est invalide");
            }

            if (empty($item['taxGroup']) || !in_array($item['taxGroup'], self::TAX_GROUPS)) {
                throw new \Exception("Le groupe de taxation de l'article à l'index " . $index . " est invalide");
            }
        }

        // Validation de l'opérateur
        if (empty($data['operator']['name'])) {
            throw new \Exception('Le nom de l\'opérateur est obligatoire');
        }

        // Validation des paiements
        if (isset($data['payment'])) {
            foreach ($data['payment'] as $index => $payment) {
                if (empty($payment['name']) || !array_key_exists($payment['name'], self::PAYMENT_TYPES)) {
                    throw new \Exception("Le type de paiement à l'index " . $index . " est invalide");
                }

                if (!isset($payment['amount']) || !is_numeric($payment['amount'])) {
                    throw new \Exception("Le montant du paiement à l'index " . $index . " est invalide");
                }
            }
        }

        return $data;
    }

    /**
     * Gérer les erreurs de l'API.
     *
     * @param RequestException $e L'exception de requête.
     * @return array<string, mixed> Les détails de l'erreur.
     */
    private function handleError(RequestException $e): array
    {
        $response = $e->getResponse();
        $body = null;
        
        if ($response) {
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $data = (array) json_decode($body, true);

            if ($statusCode === 401) {
                return [
                    'success' => false,
                    'error' => 'Erreur d\'authentification avec l\'API e-MECeF',
                    'details' => $data
                ];
            }

            return [
                'success' => false,
                'status' => $statusCode,
                'error' => $data['message'] ?? $data['error'] ?? 'Erreur de communication avec l\'API e-MECeF',
                'details' => $data
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur de communication avec l\'API e-MECeF: ' . $e->getMessage(),
        ];
    }
}
