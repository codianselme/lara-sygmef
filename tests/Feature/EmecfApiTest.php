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
    public function test_get_api_status(): void
    {
        $this->mock(EmecfService::class, function ($mock) {
            $mock->shouldReceive('getStatus')
                ->once()
                ->andReturn([
                    'success' => true,
                    'data' => ['status' => 'ok']
                ]);
        });

        $response = $this->getJson('/api/emecf/status');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => ['status' => 'ok']
                 ]);
    }

    /**
     * Test de récupération des informations du contribuable
     */
    public function test_get_taxpayer_info(): void
    {
        $this->mock(EmecfService::class, function ($mock) {
            $mock->shouldReceive('getTaxpayerInfo')
                ->once()
                ->andReturn([
                    'success' => true,
                    'data' => ['ifu' => '1234567890123', 'name' => 'Test Taxpayer']
                ]);
        });

        $response = $this->getJson('/api/emecf/taxpayer');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => ['ifu' => '1234567890123', 'name' => 'Test Taxpayer']
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
                        'total' => 20000,
                    ],
                    'invoice_id' => 1
                ]);
        });

        $response = $this->postJson('/api/emecf/invoices', $invoiceData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'uid',
                         'total'
                     ],
                     'invoice_id'
                 ])
                 ->assertJson([
                     'success' => true,
                     'invoice_id' => 1
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
}
