# Processus Complet de Facturation e-MECeF

## 📋 Vue d'ensemble

Le processus de facturation e-MECeF se déroule en **2 étapes** :

```
1. CRÉATION (pending) → 2. CONFIRMATION (confirmed + QR Code)
```

---

## Étape 1 : Création de la Facture (Statut: `pending`)

### Endpoint
```
POST /api/emecf/invoices
```

### Exemple de requête
```bash
curl -X POST http://localhost:8000/api/emecf/invoices \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "ifu": "0202113169876",
    "type": "FV",
    "operator": {
      "name": "John Doe"
    },
    "client": {
      "name": "Client Example",
      "contact": "+22997000000"
    },
    "items": [
      {
        "name": "Article 1",
        "price": 5000,
        "quantity": 2,
        "taxGroup": "B"
      }
    ],
    "payment": [
      {
        "name": "ESPECES",
        "amount": 11800
      }
    ]
  }'
```

### Réponse
```json
{
  "success": true,
  "data": {
    "invoice_id": 1,
    "uid": "978cc249-2d4f-4fdf-bcdb-6fab18540824",
    "status": "pending",
    "total": 10000,
    "calculated_amounts": {
      "ta": 0,
      "tb": 18,
      "tab": 10000,
      "hab": 8475,
      "vab": 1525,
      "total": 10000
    }
  }
}
```

**Important** : Conservez l'`uid` pour l'étape suivante !

---

## Étape 2 : Confirmation de la Facture (Récupération du QR Code)

### Endpoint
```
PUT /api/emecf/invoices/{uid}/finalize
```

### Exemple de requête
```bash
curl -X PUT http://localhost:8000/api/emecf/invoices/978cc249-2d4f-4fdf-bcdb-6fab18540824/finalize \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"action": "confirm"}'
```

### Réponse avec éléments de sécurité
```json
{
  "success": true,
  "data": {
    "invoice_id": 1,
    "uid": "978cc249-2d4f-4fdf-bcdb-6fab18540824",
    "status": "confirmed",
    "security_elements": {
      "dateTime": "22/11/2025 17:29:24",
      "qrCode": "F;TS01005852;TESTRSHH5KIJOGR4RDZLMVPW;0202113169876;20251122172924",
      "codeMECeFDGI": "TEST-RSHH-5KIJ-OGR4-RDZL-MVPW",
      "counters": "167/208 FV",
      "nim": "TS01005852"
    }
  }
}
```

---

## Utilisation dans votre Application Laravel

### Injection du Service
```php
use Codianselme\LaraSygmef\Services\EmecfService;

class InvoiceController extends Controller
{
    public function __construct(
        private EmecfService $emecfService
    ) {}
    
### 3. Cas particulier : Facture d'Avoir (FA / EA)

Pour créer une facture d'avoir, vous devez fournir la référence de la facture originale.

**⚠️ IMPORTANT :** La référence doit être le **Code MECeF/DGI** de la facture originale, **sans les tirets** (24 caractères exactement). Ne pas utiliser l'UID.

Exemple : Si le Code MECeF est `TEST-2TJK-LKV6-722Q-ZNX2-U6PO`, la référence sera `TEST2TJKLKV6722QZNX2U6PO`.

```php
$invoiceData = [
    'ifu' => '0202113169876',
    'type' => 'FA', // Facture d'Avoir
    'reference' => 'TEST2TJKLKV6722QZNX2U6PO', // Code MECeF sans tirets
    'operator' => ['name' => 'Employé 1'],
    'items' => [ ... ], // Articles retournés
    'payment' => [ ... ] // Remboursement
];
```
    public function create(Request $request)
    {
        // Étape 1 : Créer la facture
        $result = $this->emecfService->submitInvoice($request->all());
        
        if (!$result['success']) {
            return response()->json($result, 400);
        }
        
        $uid = $result['data']['uid'];
        
        // Sauvegarder en base de données...
        
        return response()->json([
            'message' => 'Facture créée',
            'uid' => $uid,
            'status' => 'pending'
        ]);
    }
    
    public function confirm(string $uid)
    {
        // Étape 2 : Confirmer et récupérer le QR code
        $result = $this->emecfService->finalizeInvoice($uid, 'confirm');
        
        if (!$result['success']) {
            return response()->json($result, 400);
        }
        
        // Mettre à jour en base de données avec le QR code
        $invoice = EmecfInvoice::where('uid', $uid)->first();
        $invoice->update([
            'status' => 'confirmed',
            'code_mec_ef_dgi' => $result['data']['codeMECeFDGI'],
            'qr_code' => $result['data']['qrCode'],
            'date_time' => $result['data']['dateTime'],
            'counters' => $result['data']['counters'],
            'nim' => $result['data']['nim'],
        ]);
        
        return response()->json([
            'message' => 'Facture confirmée',
            'qr_code' => $result['data']['qrCode'],
            'code_mecef' => $result['data']['codeMECeFDGI']
        ]);
    }
}
```

---

## Annulation d'une Facture

Si vous devez annuler une facture en attente :

```php
$result = $this->emecfService->finalizeInvoice($uid, 'cancel');
```

---

## Format du QR Code

Le QR code retourné a le format suivant :
```
F;{NIM};{CODE_COURT};{IFU};{DATETIME}
```

Exemple :
```
F;TS01005852;TESTRSHH5KIJOGR4RDZLMVPW;0202113169876;20251122172924
```

Ce QR code doit être affiché sur la facture client pour validation DGI.

---

## Codes d'Erreur Possibles

| Code | Description |
|------|-------------|
| 1    | Nombre maximum de factures en attente dépassé |
| 3    | Type de facture invalide |
| 8    | La facture doit contenir des articles |
| 9    | Groupe de taxation invalide |
| 20   | La facture n'existe pas ou est déjà finalisée/annulée |
| 99   | Erreur lors du traitement |

---

## Test Manuel Complet

Un script de test complet est disponible :

```bash
chmod +x demo-complete-flow.sh
./demo-complete-flow.sh
```

Ce script démontre le processus complet de création → confirmation avec récupération du QR code.
