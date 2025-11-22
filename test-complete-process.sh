#!/bin/bash

# Script de test complet du processus e-MECeF
# 1. Créer une facture (statut: pending)
# 2. Confirmer la facture (statut: confirmed + QR code)

echo "==================================="
echo "TEST COMPLET DU PROCESSUS E-MECEF"
echo "==================================="
echo ""

# Configuration
BASE_URL="http://127.0.0.1:8001/api/emecf"

echo "📝 Étape 1 : Création d'une facture..."
echo ""

# Créer la facture
RESPONSE=$(curl -s -X POST ${BASE_URL}/invoices \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "ifu": "0202113169876",
    "type": "FV",
    "operator": {
      "name": "John Doe"
    },
    "client": {
      "name": "Client Test",
      "contact": "+22997000000"
    },
    "items": [
      {
        "name": "Produit Test",
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

echo "Réponse création :"
echo "$RESPONSE" | jq .

# Extraire l'UID de la facture
UID=$(echo "$RESPONSE" | jq -r '.data.uid // empty')

if [ -z "$UID" ]; then
    echo ""
    echo "❌ Erreur : Impossible de créer la facture"
    echo "$RESPONSE" | jq .
    exit 1
fi

echo ""
echo "✅ Facture créée avec succès !"
echo "   UID: $UID"
echo ""

# Attendre un peu
sleep 2

echo "📋 Étape 2 : Récupération des détails de la facture en attente..."
echo ""

DETAILS=$(curl -s -X GET ${BASE_URL}/api/invoice/${UID} \
  -H "Accept: application/json")

echo "$DETAILS" | jq .
echo ""

# Attendre un peu
sleep 2

echo "✅ Étape 3 : Confirmation de la facture (récupération du QR code)..."
echo ""

CONFIRM=$(curl -s -X PUT ${BASE_URL}/invoices/${UID}/finalize \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"action": "confirm"}')

echo "$CONFIRM" | jq .

# Vérifier si on a un QR code
QR_CODE=$(echo "$CONFIRM" | jq -r '.data.security_elements.qrCode // .data.qrCode // empty')

if [ -n "$QR_CODE" ]; then
    echo ""
    echo "🎉 SUCCÈS COMPLET !"
    echo "   📱 QR Code reçu"
    echo "   Code MECeF/DGI: $(echo "$CONFIRM" | jq -r '.data.security_elements.codeMECeFDGI // .data.codeMECeFDGI // "N/A"')"
    echo "   Date/Heure: $(echo "$CONFIRM" | jq -r '.data.security_elements.dateTime // .data.dateTime // "N/A"')"
    echo "   Compteurs: $(echo "$CONFIRM" | jq -r '.data.security_elements.counters // .data.counters // "N/A"')"
    echo "   NIM: $(echo "$CONFIRM" | jq -r '.data.security_elements.nim // .data.nim // "N/A"')"
else
    echo ""
    echo "⚠️  Facture confirmée mais réponse inattendue"
fi

echo ""
echo "==================================="
echo "FIN DU TEST"
echo "==================================="
