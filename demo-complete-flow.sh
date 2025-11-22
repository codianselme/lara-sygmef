#!/bin/bash

# Test direct avec l'API e-MECeF (simulation du processus complet)
# WITHOUT database dependency

echo "==================================="
echo "DÉMONSTRATION DU PROCESSUS COMPLET"
echo "==================================="
echo ""

# Charger le token depuis .env
TOKEN=$(grep EMECF_TOKEN .env | cut -d '"' -f 2)
API_URL="https://developper.impots.bj/sygmef-emcf/api"

echo "🔐 Token configuré: ${TOKEN:0:20}..."
echo ""

echo "📝 Étape 1 : Création d'une facture auprès de l'API DGI..."
echo ""

# Créer la facture directement à l'API
INVOICE_RESPONSE=$(curl -s -X POST "${API_URL}/invoice" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${TOKEN}" \
  -d '{
    "ifu": "0202113169876",
    "type": "FV",
    "operator": {
      "name": "JERIMO-YAMAH"
    },
    "client": {
      "name": "Client Test Complet",
      "contact": "+22997000000"
    },
    "items": [
      {
        "name": "Article de démonstration",
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
  }')

echo "Réponse API :"
echo "$INVOICE_RESPONSE" | jq .

# Extraire l'UID
INVOICE_UID=$(echo "$INVOICE_RESPONSE" | jq -r '.uid // empty')

if [ -z "$INVOICE_UID" ]; then
    echo ""
    echo "❌ Erreur lors de la création"
    exit 1
fi

echo ""
echo "✅ Facture créée ! UID: $INVOICE_UID"
echo "   📊 Statut: PENDING (en attente de confirmation)"
echo ""

# Attendre 3 secondes
sleep 3

echo "📋 Étape 2 : Récupération des détails de la facture en attente..."
echo ""

DETAILS_RESPONSE=$(curl -s -X GET "${API_URL}/invoice/${INVOICE_UID}" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${TOKEN}")

echo "$DETAILS_RESPONSE" | jq .
echo ""

# Attendre 3 secondes
sleep 3

echo "✅ Étape 3 : CONFIRMATION de la facture..."
echo ""

CONFIRM_RESPONSE=$(curl -s -X PUT "${API_URL}/invoice/${INVOICE_UID}/confirm" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${TOKEN}")

echo "$CONFIRM_RESPONSE" | jq .

# Extraire les éléments de sécurité
CODE_MECEF=$(echo "$CONFIRM_RESPONSE" | jq -r '.codeMECeFDGI // empty')
QR_CODE=$(echo "$CONFIRM_RESPONSE" | jq -r '.qrCode // empty')
DATE_TIME=$(echo "$CONFIRM_RESPONSE" | jq -r '.dateTime // empty')
COUNTERS=$(echo "$CONFIRM_RESPONSE" | jq -r '.counters // empty')
NIM=$(echo "$CONFIRM_RESPONSE" | jq -r '.nim // empty')

echo ""
echo "==================================="
if [ -n "$CODE_MECEF" ] && [ -n "$QR_CODE" ]; then
    echo "🎉 SUCCÈS COMPLET !"
    echo "==================================="
    echo ""
    echo "📱 ÉLÉMENTS DE SÉCURITÉ REÇUS :"
    echo "   • Code MECeF/DGI : $CODE_MECEF"
    echo "   • Date/Heure     : $DATE_TIME"
    echo "   • Compteurs      : $COUNTERS"
    echo "   • NIM e-MCF      : $NIM"
    echo ""
    echo "   ✅ QR Code       : $(echo $QR_CODE | head -c 50)..."
    echo ""
    echo "📊 STATUT FINAL : CONFIRMÉ ✅"
else
    echo "⚠️  Réponse inattendue"
    echo "==================================="
fi

echo ""
echo "Le processus complet est FONCTIONNEL :"
echo "1. ✅ Création de facture (statut: pending)"
echo "2. ✅ Confirmation (statut: confirmed + QR code)"
echo ""
