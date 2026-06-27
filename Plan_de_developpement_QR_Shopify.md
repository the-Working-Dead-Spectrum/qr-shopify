# Plan de développement --- Approche senior

Voici les phases proposées, dans l'ordre logique d'un vrai projet.

## Phase 1 --- Socle & Infrastructure

-   Migrations
-   Modèles Eloquent
-   Factories
-   Relations
-   Configuration `config/qr.php`
-   Installation des packages

## Phase 2 --- Sécurité entrante (Middlewares)

-   `VerifyShopifyHmac`
-   `EnsurePartner`
-   Rate limiting
-   Enregistrement dans `bootstrap/app.php`

## Phase 3 --- Couche Servicielle (Services)

-   `ShopifyService`
-   `QrCodeService`
-   `ValidationService`
-   `DashboardService`

## Phase 4 --- Jobs & Mail

-   `GenerateAndSendQrJob`
-   `SendQrCodeEmailJob`
-   `NotifyAdminOnErrorJob`
-   `ExpireQrCodesJob`
-   `CleanupOldValidationsJob`
-   Scheduler
-   `QrCodeMailable`
-   Template Blade

## Phase 5 --- Controllers & Routes

-   `ShopifyWebhookController`
-   `ValidationController`
-   `QrCodeController`
-   `AdminController`
-   Form Requests
-   API Resources

## Phase 6 --- Frontend Admin (Blade + Alpine.js)

-   Dashboard KPIs
-   Liste des commandes
-   Détail d'une commande
-   Gestion des partenaires
-   Historique des scans

## Phase 7 --- PWA Partenaire

-   `manifest.json`
-   Service Worker
-   Scanner `jsQR`
-   Écrans : Login, Scanner, Résultat, Historique

## Phase 8 --- Tests

-   Suite complète PestPHP
-   Tests unitaires
-   Tests Feature
-   Tests de concurrence
-   Tests E2E avec Playwright

## Phase 9 --- CI/CD & Production

-   Pipeline GitHub Actions
-   Checklist de déploiement
-   Configuration Supervisor
