# Installation du package Laravel e-MECeF

## Installation via Composer

\`\`\`bash
composer require codianselme/lara-sygmef
\`\`\`

## Configuration

### 1. Publier les fichiers du package

\`\`\`bash
# Publier la configuration
php artisan vendor:publish --tag=emecf-config

# Publier les migrations
php artisan vendor:publish --tag=emecf-migrations

# Publier les routes (optionnel)
php artisan vendor:publish --tag=emecf-routes
\`\`\`

### 2. Exécuter les migrations

\`\`\`bash
php artisan migrate
\`\`\`

### 3. Configurer les variables d'environnement

Ajoutez à votre fichier \`.env\` :

\`\`\`env
EMECF_TEST_MODE=true
EMECF_TOKEN=votre_token_jwt
EMECF_TIMEOUT=30
EMECF_CONNECT_TIMEOUT=10
EMECF_RETRY=3
EMECF_SAVE_INVOICES=true
EMECF_SAVE_LOGS=true
\`\`\`

### 4. Utiliser le service

\`\`\`php
use Codianselme\\LaraSygmef\\Services\\EmecfService;

$emecf = app(EmecfService::class);
$result = $emecf->getInvoiceStatus();
\`\`\`

## Routes API

Les routes sont automatiquement disponibles si vous avez publié les fichiers de routes :

- \`/api/emecf/status\` - Statut de l'API
- \`/api/emecf/invoices\` - Gestion des factures
- \`/api/emecf/info/*\` - Informations e-MECeF

## Support

Consultez le fichier README.md pour la documentation complète.