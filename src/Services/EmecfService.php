<?php

namespace Codianselme\LaraSygmef\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Exception;

class EmecfService
{
    private Client $client;
    private string $baseUrl;
    private string $token;
    private bool $isTestMode;

    /**
     * Types de factures disponibles
     */
    public const INVOICE_TYPES = [
        'FV' => 'Facture de vente',
        'EV' => 'Facture de vente à l\'exportation',
        'FA' => 'Facture d\'avoir',
        'EA' => 'Facture d\'avoir à l\'exportation'
    ];

    /**
     * Types de paiement disponibles
     */
    public const PAYMENT_TYPES = [
        'ESPECES' => 'ESPECES',
        'VIREMENT' => 'VIREMENT',
        'CARTEBANCAIRE' => 'CARTE BANCAIRE',
        'MOBILEMONEY' => 'MOBILE MONEY',
        'CHEQUES' => 'CHEQUES',
        'CREDIT' => 'CREDIT',
        'AUTRE' => 'AUTRE'
    ];

    /**
     * Groupes de taxation
     */
    public const TAX_GROUPS = ['A', 'B', 'C', 'D', 'E', 'F'];

    /**
     * Types AIB
     */
    public const AIB_TYPES = ['A', 'B'];

    /**
     * Codes d'erreur API
     */
    public const ERROR_CODES = [
        1 => 'Le nombre maximum de factures en attente est dépassé',
        3 => 'Type de facture n\'est pas valide',
        4 => 'La référence de la facture originale est manquante',
        5 => 'La référence de la facture originale ne comporte pas 24 caractères',
        6 => 'La valeur de l\'AIB n\'est pas valide',
        7 => 'Le type de paiement n\'est pas valide',
        8 => 'La facture doit contenir les articles',
        9 => 'Le groupe de taxation au niveau des articles n\'est pas valide',
        10 => 'La référence de la facture originale ne peut pas être validée, veuillez réessayer plus tard',
        11 => 'La référence de la facture originale n\'est pas valide (la facture originale est introuvable)',
        12 => 'La référence de la facture originale n\'est pas valide (le montant sur la facture d\'avoir dépassé le montant de la facture originale)',
        20 => 'La facture n\'existe pas ou elle est déjà finalisée / annulée',
        99 => 'Erreur lors du traitement de la demande'
    ];

    public function __construct()
    {
        $this->isTestMode = config('emecf.test_mode', true);
        $this->baseUrl = $this->isTestMode 
            ? 'https://developper.impots.bj/sygmef-emcf/api/' 
            : 'https://sygmef.impots.bj/emcf/api/';
        
        $this->token = config('emecf.token');
        
        if (!$this->token) {
            throw new Exception('Token e-MECeF non configuré');
        }

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'timeout' => 30
        ]);
    }

    /**
     * Vérifier le statut de l'API de facturation
     */
    public function getInvoiceStatus(): array
    {
        try {
            $response = $this->client->get('invoice');
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'data' => $data
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Soumettre une demande de facture
     */
    public function submitInvoice(array $invoiceData): array
    {
        try {
            $validatedData = $this->validateInvoiceData($invoiceData);
            
            $response = $this->client->post('invoice', [
                'json' => $validatedData
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'data' => $data
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Finaliser une facture (confirmer ou annuler)
     */
    public function finalizeInvoice(string $uid, string $action = 'confirm'): array
    {
        try {
            if (!in_array($action, ['confirm', 'cancel'])) {
                throw new Exception('L\'action doit être "confirm" ou "cancel"');
            }

            $response = $this->client->put("invoice/{$uid}/{$action}", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Content-Length' => '0'
                ]
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'data' => $data
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Obtenir les détails d'une facture en attente
     */
    public function getPendingInvoiceDetails(string $uid): array
    {
        try {
            $response = $this->client->get("invoice/{$uid}");
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'data' => $data
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Obtenir les informations sur les e-MCF
     */
    public function getEmcfInfo(): array
    {
        try {
            $response = $this->client->get('info/status');
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'data' => $data
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Obtenir les informations sur les groupes de taxation
     */
    public function getTaxGroups(): array
    {
        try {
            $response = $this->client->get('info/taxGroups');
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'data' => $data
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Obtenir les types de factures disponibles
     */
    public function getInvoiceTypes(): array
    {
        try {
            $response = $this->client->get('info/invoiceTypes');
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'data' => $data
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Obtenir les types de paiement disponibles
     */
    public function getPaymentTypes(): array
    {
        try {
            $response = $this->client->get('info/paymentTypes');
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'data' => $data
            ];
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Valider les données de facture
     */
    private function validateInvoiceData(array $data): array
    {
        // Validation des champs obligatoires
        if (empty($data['ifu'])) {
            throw new Exception('L\'IFU est obligatoire');
        }

        if (empty($data['type']) || !array_key_exists($data['type'], self::INVOICE_TYPES)) {
            throw new Exception('Le type de facture est invalide');
        }

        if (empty($data['items']) || !is_array($data['items'])) {
            throw new Exception('La facture doit contenir au moins un article');
        }

        // Validation pour les factures d'avoir
        if (in_array($data['type'], ['FA', 'EA']) && empty($data['reference'])) {
            throw new Exception('La référence de la facture originale est obligatoire pour les factures d\'avoir');
        }

        if (in_array($data['type'], ['FA', 'EA']) && strlen($data['reference']) !== 24) {
            throw new Exception('La référence de la facture originale doit contenir 24 caractères');
        }

        // Validation des articles
        foreach ($data['items'] as $index => $item) {
            if (empty($item['name'])) {
                throw new Exception("Le nom de l'article à l'index {$index} est obligatoire");
            }

            if (!isset($item['price']) || !is_numeric($item['price'])) {
                throw new Exception("Le prix de l'article à l'index {$index} est invalide");
            }

            if (!isset($item['quantity']) || !is_numeric($item['quantity'])) {
                throw new Exception("La quantité de l'article à l'index {$index} est invalide");
            }

            if (empty($item['taxGroup']) || !in_array($item['taxGroup'], self::TAX_GROUPS)) {
                throw new Exception("Le groupe de taxation de l'article à l'index {$index} est invalide");
            }
        }

        // Validation de l'opérateur
        if (empty($data['operator']['name'])) {
            throw new Exception('Le nom de l\'opérateur est obligatoire');
        }

        // Validation des paiements
        if (isset($data['payment'])) {
            foreach ($data['payment'] as $index => $payment) {
                if (empty($payment['name']) || !array_key_exists($payment['name'], self::PAYMENT_TYPES)) {
                    throw new Exception("Le type de paiement à l'index {$index} est invalide");
                }

                if (!isset($payment['amount']) || !is_numeric($payment['amount'])) {
                    throw new Exception("Le montant du paiement à l'index {$index} est invalide");
                }
            }
        }

        return $data;
    }

    /**
     * Gérer les erreurs de l'API
     */
    private function handleError(RequestException $e): array
    {
        $response = $e->getResponse();
        $body = null;
        
        if ($response) {
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if ($statusCode === 401) {
                return [
                    'success' => false,
                    'error' => 'Token invalide ou expiré',
                    'error_code' => 401
                ];
            }

            if ($statusCode === 400 && isset($data['errorCode'])) {
                $errorMessage = self::ERROR_CODES[$data['errorCode']] ?? 'Erreur inconnue';
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_code' => $data['errorCode'],
                    'error_desc' => $data['errorDesc'] ?? null
                ];
            }

            // Pour tout autre code d'erreur, retourner les détails complets
            Log::error('e-MECeF API Error', [
                'status_code' => $statusCode,
                'exception' => $e->getMessage(),
                'response_body' => $body,
                'response_data' => $data
            ]);

            return [
                'success' => false,
                'error' => $data['message'] ?? $data['error'] ?? 'Erreur de communication avec l\'API e-MECeF',
                'error_code' => $statusCode,
                'error_desc' => $body,
                'api_response' => $data
            ];
        }

        Log::error('e-MECeF API Error', [
            'exception' => $e->getMessage(),
            'response' => 'No response'
        ]);

        return [
            'success' => false,
            'error' => 'Erreur de communication avec l\'API e-MECeF: ' . $e->getMessage(),
            'error_code' => 500
        ];
    }

    /**
     * Obtenir l'URL de base de l'API
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Vérifier si le service est en mode test
     */
    public function isTestMode(): bool
    {
        return $this->isTestMode;
    }
}
