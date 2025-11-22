# Service e-MECeF pour Laravel

Un package Laravel complet pour l'intégration de l'API e-MECeF (Module de contrôle dématérialisé) de la DGI du Bénin.

## 📋 Table des matières

- [Introduction](#introduction)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [API Endpoints](#api-endpoints)
- [Modèles de données](#modèles-de-données)
- [Tests](#tests)
- [Licence](#licence)

## 🚀 Introduction

Ce package permet de gérer les factures normalisées conformément aux exigences de la Direction Générale des Impôts (DGI) du Bénin. Il implémente la version 1.0 de l'API e-MECeF et offre :

- Soumission, finalisation et annulation de factures
- Calcul automatique des taxes (Groupes A-F)
- Stockage local des transactions
- Gestion des erreurs et retries automatiques

## 📦 Installation

Installez le package via Composer :

```bash
composer require codianselme/lara-sygmef
```

## ⚙️ Configuration

### 1. Publier les fichiers de configuration

```bash
php artisan vendor:publish --tag=emecf-config
```

Cela créera le fichier `config/emecf.php`.

### 2. Publier les migrations

```bash
php artisan vendor:publish --tag=emecf-migrations
php artisan migrate
```

### 3. Variables d'environnement

Ajoutez les clés suivantes à votre fichier `.env` :

```env
EMECF_TEST_MODE=true
EMECF_TOKEN=votre_token_jwt_ici
EMECF_TIMEOUT=30
EMECF_CONNECT_TIMEOUT=10
EMECF_RETRY=3
EMECF_SAVE_INVOICES=true
EMECF_SAVE_LOGS=true
```

## 🎯 Utilisation

### Via le Service

```php
use Codianselme\LaraSygmef\Services\EmecfService;

$emecf = app(EmecfService::class);

// Vérifier le statut
$status = $emecf->getInvoiceStatus();

// Soumettre une facture
$invoiceData = [
    'ifu' => '1234567890123',
    'type' => 'FV',
    'operator' => ['name' => 'Mon Entreprise'],
    'items' => [
        [
            'name' => 'Produit A',
            'price' => 10000,
            'quantity' => 2,
            'taxGroup' => 'B'
        ]
    ],
    'payment' => [
        ['name' => 'ESPECES', 'amount' => 20000]
    ]
];

$result = $emecf->submitInvoice($invoiceData);
```

### Via les Routes API

Si vous avez publié les routes (`php artisan vendor:publish --tag=emecf-routes`), vous pouvez utiliser les endpoints suivants :

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/emecf/status` | Statut de l'API |
| `POST` | `/emecf/invoices` | Soumettre une facture |
| `PUT` | `/emecf/invoices/{uid}/finalize` | Finaliser (confirm/cancel) |
| `GET` | `/emecf/invoices/{uid}/pending` | Détails facture en attente |

## 🧪 Tests

Pour lancer les tests du package :

```bash
composer test
```

## 📄 Licence

Ce package est sous licence MIT.
