<?php

namespace Codianselme\LaraSygmef\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Codianselme\LaraSygmef\Tests\TestCase;
use Codianselme\LaraSygmef\Models\EmecfInvoice;
use Codianselme\LaraSygmef\Services\EmecfService;

class EmecfApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test de récupération du statut de l'API
     */
    public function test_get_invoice_status(): void
    {
        $this->mock(EmecfService::class, function ($mock) {
            $mock->shouldReceive('getInvoiceStatus')
                ->once()
                ->andReturn([
                    'success' => true,
                    'data' => ['status' => 'ok']
                ]);
        });

        $response = $this->getJson('/api/emecf/status');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                 ]);
    }

    /**
     * Test de soumission d'une facture valide
     */
    public function test_submit_valid_invoice(): void
    {
        $invoiceData = [
            'ifu' => '1234567890123',
            'type' => 'FV',
            'operator' => [
                'name' => 'Entreprise Test SARL'
            ],
            'items' => [
                [
                    'name' => 'Produit Test',
                    'price' => 10000,
                    'quantity' => 2,
                    'taxGroup' => 'B'
                ]
            ],
            'payment' => [
                [
                    'name' => 'ESPECES',
                    'amount' => 20000
                ]
            ]
        ];

        $this->mock(EmecfService::class, function ($mock) {
            $mock->shouldReceive('submitInvoice')
                ->once()
                ->andReturn([
                    'success' => true,
                    'data' => [
                        'uid' => 'test-uid-' . uniqid(),
                        'ta' => 0,
                        'tb' => 0,
                        'tc' => 0,
                        'td' => 0,
                        'taa' => 0,
                        'tab' => 0,
                        'tac' => 0,
                        'tad' => 0,
                        'tae' => 0,
                        'taf' => 0,
                        'hab' => 0,
                        'had' => 0,
                        'vab' => 0,
                        'vad' => 0,
                        'aib' => 0,
                        'ts' => 0,
                        'total' => 20000,
                        'error' => null
                    ]
                ]);
        });

        $response = $this->postJson('/api/emecf/invoices', $invoiceData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'invoice_id',
                         'uid',
                         'status',
                         'total',
                         'calculated_amounts'
                     ]
                 ]);
    }

    /**
     * Test de soumission d'une facture invalide
     */
    public function test_submit_invalid_invoice(): void
    {
        $invalidData = [
            'ifu' => '123', // IFU invalide
            'type' => 'INVALID', // Type invalide
            'operator' => [
                'name' => '' // Nom vide
            ],
            'items' => [] // Pas d'articles
        ];

        $response = $this->postJson('/api/emecf/invoices', $invalidData);

        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'message',
                     'errors'
                 ]);
    }

    /**
     * Test de validation des champs obligatoires
     */
    public function test_invoice_required_fields_validation(): void
    {
        $response = $this->postJson('/api/emecf/invoices', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['ifu', 'type', 'operator', 'items']);
    }

    /**
     * Test de validation de l'IFU
     */
    public function test_ifu_validation(): void
    {
        $data = [
            'ifu' => '123456789012', // 12 chiffres au lieu de 13
            'type' => 'FV',
            'operator' => [
                'name' => 'Test'
            ],
            'items' => [
                [
                    'name' => 'Test',
                    'price' => 1000,
                    'quantity' => 1,
                    'taxGroup' => 'B'
                ]
            ]
        ];

        $response = $this->postJson('/api/emecf/invoices', $data);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['ifu']);
    }

    /**
     * Test de validation des articles
     */
    public function test_items_validation(): void
    {
        $data = [
            'ifu' => '1234567890123',
            'type' => 'FV',
            'operator' => [
                'name' => 'Test'
            ],
            'items' => [
                [
                    'name' => '', // Nom vide
                    'price' => -100, // Prix négatif
                    'quantity' => -1, // Quantité négative
                    'taxGroup' => 'X' // Groupe invalide
                ]
            ]
        ];

        $response = $this->postJson('/api/emecf/invoices', $data);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'items.0.name',
                     'items.0.price',
                     'items.0.quantity',
                     'items.0.taxGroup'
                 ]);
    }

    /**
     * Test de validation des paiements
     */
    public function test_payment_validation(): void
    {
        $data = [
            'ifu' => '1234567890123',
            'type' => 'FV',
            'operator' => [
                'name' => 'Test'
            ],
            'items' => [
                [
                    'name' => 'Test',
                    'price' => 10000,
                    'quantity' => 1,
                    'taxGroup' => 'B'
                ]
            ],
            'payment' => [
                [
                    'name' => 'INVALID_PAYMENT', // Type invalide
                    'amount' => 5000 // Montant ne correspond pas au total
                ]
            ]
        ];

        $response = $this->postJson('/api/emecf/invoices', $data);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'payment.0.name'
                 ]);
    }

    /**
     * Test des types de facture
     */
    public function test_invoice_types(): void
    {
        $validTypes = ['FV', 'EV', 'FA', 'EA'];
        
        $this->mock(EmecfService::class, function ($mock) use ($validTypes) {
            $mock->shouldReceive('submitInvoice')
                ->times(count($validTypes))
                ->andReturnUsing(function () {
                    return ['success' => true, 'data' => ['uid' => uniqid(), 'total' => 1000]];
                });
        });

        foreach ($validTypes as $type) {
            $data = [
                'ifu' => '1234567890123',
                'type' => $type,
                'operator' => [
                    'name' => 'Test'
                ],
                'items' => [
                    [
                        'name' => 'Test',
                        'price' => 1000,
                        'quantity' => 1,
                        'taxGroup' => 'B'
                    ]
                ]
            ];

            if (in_array($type, ['FA', 'EA'])) {
                $data['reference'] = '123456789012345678901234'; // 24 chars
            }

            $response = $this->postJson('/api/emecf/invoices', $data);
            
            // Le type de facture est valide, donc pas d'erreur de validation
            $response->assertSuccessful();
        }
    }

    /**
     * Test des groupes de taxation
     */
    public function test_tax_groups(): void
    {
        $validGroups = ['A', 'B', 'C', 'D', 'E', 'F'];
        
        $this->mock(EmecfService::class, function ($mock) use ($validGroups) {
            $mock->shouldReceive('submitInvoice')
                ->times(count($validGroups))
                ->andReturnUsing(function () {
                    return ['success' => true, 'data' => ['uid' => uniqid(), 'total' => 1000]];
                });
        });

        foreach ($validGroups as $group) {
            $data = [
                'ifu' => '1234567890123',
                'type' => 'FV',
                'operator' => [
                    'name' => 'Test'
                ],
                'items' => [
                    [
                        'name' => 'Test',
                        'price' => 1000,
                        'quantity' => 1,
                        'taxGroup' => $group
                    ]
                ]
            ];

            $response = $this->postJson('/api/emecf/invoices', $data);
            
            // Le groupe de taxation est valide, donc pas d'erreur de validation
            $response->assertSuccessful();
        }
    }

    /**
     * Test de récupération des informations e-MECeF
     */
    public function test_get_emecf_info(): void
    {
        $this->mock(EmecfService::class, function ($mock) {
            $mock->shouldReceive('getEmcfInfo')
                ->once()
                ->andReturn([
                    'success' => true,
                    'data' => ['status' => 'ok']
                ]);
        });

        $response = $this->getJson('/api/emecf/info/emcf');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                 ]);
    }

    /**
     * Test de récupération des groupes de taxation
     */
    public function test_get_tax_groups(): void
    {
        $this->mock(EmecfService::class, function ($mock) {
            $mock->shouldReceive('getTaxGroups')
                ->once()
                ->andReturn([
                    'success' => true,
                    'data' => ['A', 'B']
                ]);
        });

        $response = $this->getJson('/api/emecf/info/tax-groups');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                 ]);
    }

    /**
     * Test de récupération des types de factures
     */
    public function test_get_invoice_types(): void
    {
        $this->mock(EmecfService::class, function ($mock) {
            $mock->shouldReceive('getInvoiceTypes')
                ->once()
                ->andReturn([
                    'success' => true,
                    'data' => ['FV', 'EV']
                ]);
        });

        $response = $this->getJson('/api/emecf/info/invoice-types');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                 ]);
    }

    /**
     * Test de récupération des types de paiement
     */
    public function test_get_payment_types(): void
    {
        $this->mock(EmecfService::class, function ($mock) {
            $mock->shouldReceive('getPaymentTypes')
                ->once()
                ->andReturn([
                    'success' => true,
                    'data' => ['ESPECES', 'VIREMENT']
                ]);
        });

        $response = $this->getJson('/api/emecf/info/payment-types');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                 ]);
    }

    /**
     * Test de listing des factures locales
     */
    public function test_list_invoices(): void
    {
        // Créer une facture de test
        EmecfInvoice::create([
            'uid' => 'test-uid-' . uniqid(),
            'ifu' => '1234567890123',
            'type' => 'FV',
            'operator_name' => 'Test Operator',
            'total' => 10000,
            'status' => 'pending'
        ]);

        $response = $this->getJson('/api/emecf/invoices');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'data' => [
                             '*' => [
                                 'id',
                                 'uid',
                                 'ifu',
                                 'type',
                                 'operator_name',
                                 'total',
                                 'status',
                                 'created_at',
                                 'updated_at'
                             ]
                         ],
                         'current_page',
                         'last_page',
                         'per_page',
                         'total'
                     ]
                 ]);
    }

    /**
     * Test de récupération des détails d'une facture
     */
    public function test_get_invoice_details(): void
    {
        $invoice = EmecfInvoice::create([
            'uid' => 'test-uid-' . uniqid(),
            'ifu' => '1234567890123',
            'type' => 'FV',
            'operator_name' => 'Test Operator',
            'total' => 10000,
            'status' => 'pending'
        ]);

        $response = $this->getJson("/api/emecf/invoices/{$invoice->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'uid',
                         'ifu',
                         'type',
                         'operator_name',
                         'total',
                         'status',
                         'created_at',
                         'updated_at'
                     ]
                 ]);
    }

    /**
     * Test de facture non trouvée
     */
    public function test_invoice_not_found(): void
    {
        $response = $this->getJson('/api/emecf/invoices/99999');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'error' => 'Facture non trouvée'
                 ]);
    }
}
