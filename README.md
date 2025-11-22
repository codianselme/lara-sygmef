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

### Processus Complet (2 Étapes)

Le processus de facturation e-MECeF se déroule en **2 étapes** :

#### Étape 1 : Création de la Facture (Statut: `pending`)

```php
use Codianselme\LaraSygmef\Services\EmecfService;

$emecf = app(EmecfService::class);

// Données de la facture
$invoiceData = [
    'ifu' => '0202113169876',
    'type' => 'FV',
    'operator' => ['name' => 'JERIMO-YAMAH'],
    'client' => [
        'name' => 'Client Example',
        'contact' => '+22997000000'
    ],
    'items' => [
        [
            'name' => 'Produit A',
            'price' => 5000,
            'quantity' => 2,
            'taxGroup' => 'B'
        ]
    ],
    'payment' => [
        ['name' => 'ESPECES', 'amount' => 11800]
    ]
];

// Soumettre la facture
$result = $emecf->submitInvoice($invoiceData);

if ($result['success']) {
    $uid = $result['data']['uid'];
    $total = $result['data']['total'];
    // Statut : 'pending' - en attente de confirmation
}
```

#### Étape 2 : Confirmation et Récupération du QR Code

```php
// Confirmer la facture pour obtenir le QR code
$confirmation = $emecf->finalizeInvoice($uid, 'confirm');

if ($confirmation['success']) {
    $qrCode = $confirmation['data']['qrCode'];
    $codeMECeF = $confirmation['data']['codeMECeFDGI'];
    $dateTime = $confirmation['data']['dateTime'];
    $counters = $confirmation['data']['counters'];
    $nim = $confirmation['data']['nim'];
    
    // Le QR code est maintenant disponible pour l'impression sur la facture
    // Format : "F;{NIM};{CODE_COURT};{IFU};{DATETIME}"
}
```

#### Annulation d'une Facture

```php
// Annuler une facture en attente
$cancellation = $emecf->finalizeInvoice($uid, 'cancel');
```

### Autres Opérations

```php
// Vérifier le statut de l'API
$status = $emecf->getInvoiceStatus();

// Récupérer les groupes de taxation
$taxGroups = $emecf->getTaxGroups();

// Récupérer les types de factures
$invoiceTypes = $emecf->getInvoiceTypes();

// Récupérer les types de paiement
$paymentTypes = $emecf->getPaymentTypes();
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
