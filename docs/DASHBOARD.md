# 🎨 Dashboard e-MECeF - Guide d'Utilisation

## 📋 Vue d'ensemble

Le package inclut maintenant un **dashboard web complet et professionnel** pour gérer vos factures e-MECeF avec une interface moderne et intuitive.

---

## 🚀 Installation du Dashboard

### 1. Publier les routes du dashboard

```bash
php artisan vendor:publish --tag=emecf-dashboard
```

### 2. Accéder au dashboard

Une fois publié, le dashboard est accessible sur :

```
http://votre-app.test/emecf/dashboard
```

---

## ✨ Fonctionnalités

### 📊 Tableau de Bord Principal
- **Statistiques en temps réel** :
  - Total des factures
  - Factures en attente
  - Factures confirmées
  - Montant total généré
- **Graphique d'évolution** sur les 6 derniers mois
- **Liste des factures récentes**

### 📄 Gestion des Factures
- **Liste complète** avec pagination
- **Filtres avancés** :
  - Par statut (pending, confirmed, cancelled)
  - Par date (période personnalisée)
  - Recherche par UID, IFU, nom client
- **Actions en un clic** :
  -  Confirmer une facture
  - ❌ Annuler une facture
  - 👁️ Voir les détails

### ➕ Création de Facture
- **Formulaire interactif** avec validation
- **Ajout dynamique** d'articles
- **Calcul automatique** du total
- **Soumission directe** à l'API e-MECeF

### 🔍 Détails de Facture
- **Affichage du QR Code** (pour factures confirmées)
- **Code MECeF/DGI**
- **Tous les montants** (HT, TVA, Total)
- **Liste des articles**
- **Informations client**

---

## 🎨 Design & UX

### Caractéristiques du Design
✅ **Interface moderne** avec dégradés et ombres
✅ **Responsive** (mobile, tablette, desktop)
✅ **Animations fluides** au survol
✅ **Typographie professionnelle** (Google Fonts Inter)
✅ **Palette de couleurs cohérente**
✅ **Feedback visuel** pour chaque action

### Palette de Couleurs
- **Primary** : Indigo (#6366f1)
- **Success** : Vert (#10b981)
- **Warning** : Orange (#f59e0b)
- **Danger** : Rouge (#ef4444)
- **Background** : Dégradé violet

---

## 📸 Screenshots

### Dashboard Principal
```
┌─────────────────────────────────────────────┐
│  📊 Tableau de bord                      ➕  │
├─────────────────────────────────────────────┤
│  [Total: 156] [Attente: 12] [OK: 140]      │
│  💰 Montant: 2,450,000 FCFA                 │
├─────────────────────────────────────────────┤
│  📈 Graphique évolution...                  │
├─────────────────────────────────────────────┤
│  Factures récentes:                         │
│  - Facture #1234 | Client A | 50,000 FCFA  │
│  - Facture #1235 | Client B | 75,000 FCFA  │
└─────────────────────────────────────────────┘
```

---

## 🔧 Personnalisation

### Modifier les couleurs

Éditez le fichier `resources/views/dashboard/layout.blade.php` :

```css
:root {
    --primary: #votre-couleur;
    --secondary: #votre-couleur;
    /* ... */
}
```

### Ajouter des sections

Le dashboard utilise Blade, vous pouvez facilement étendre les vues :

```blade
@extends('emecf::dashboard.layout')

@section('content')
    <!-- Votre contenu -->
@endsection
```

---

## 🔐 Sécurité

### Ajouter l'authentification

Protégez les routes du dashboard en ajoutant un middleware dans `routes/dashboard.php` :

```php
Route::prefix('emecf')
    ->name('emecf.dashboard.')
    ->middleware(['web', 'auth']) // Ajoutez 'auth'
    ->group(function () {
        // Routes...
    });
```

### Autorisation

Ajoutez des policies pour contrôler l'accès :

```php
->middleware(['web', 'auth', 'can:manage-invoices'])
```

---

## 📱 Responsive Design

Le dashboard est 100% responsive :

- **Desktop** : Sidebar fixe, layout 2 colonnes
- **Tablette** : Sidebar pliable
- **Mobile** : Navigation hamburger, cartes en pile

---

## 🎯 Routes Disponibles

| Route | Description |
|-------|-------------|
| `/emecf/dashboard` | Page principale |
| `/emecf/invoices` | Liste des factures |
| `/emecf/invoices/create` | Créer une facture |
| `/emecf/invoices/{id}` | Détails d'une facture |
| `/emecf/invoices/{id}/confirm` | Confirmer (POST) |
| `/emecf/invoices/{id}/cancel` | Annuler (POST) |

---

## 💡 Conseils d'Utilisation

### Workflow Recommandé

1. **Créer** une facture via le formulaire
2. **Vérifier** les détails dans la page de détail
3. **Confirmer** la facture pour obtenir le QR code
4. **Imprimer** ou envoyer la facture au client

### Statuts des Factures

- 🟡 **Pending** : En attente de confirmation
- 🟢 **Confirmed** : Confirmée avec QR code
- 🔴 **Cancelled** : Annulée
- ⚠️ **Error** : Erreur lors du traitement

---

## 🐛 Dépannage

### Le dashboard ne s'affiche pas

1. Vérifiez que les routes sont publiées :
   ```bash
   php artisan vendor:publish --tag=emecf-dashboard
   ```

2. Vérifiez que les vues sont chargées :
   ```bash
   php artisan view:clear
   php artisan config:clear
   ```

### Les statistiques sont vides

Assurez-vous que les migrations sont exécutées :
```bash
php artisan migrate
```

---

## 🚀 Prochaines Étapes

Le dashboard est extensible ! Vous pouvez ajouter :
- Export PDF des factures
- Envoi par email
- Statistiques avancées
- Multi-utilisateurs
- Logs d'activité

---

## 📞 Support

Pour toute question sur le dashboard :
- 📧 Email : codianselme@gmail.com
- 🐛 Issues : [GitHub](https://github.com/codianselme/lara-sygmef/issues)

---

**Le dashboard est maintenant opérationnel ! Accédez-y sur `/emecf/dashboard`** 🎉
