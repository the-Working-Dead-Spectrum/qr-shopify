<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Identifiants Shopify
    |--------------------------------------------------------------------------
    |
    | shop_domain : sous-domaine de la boutique (ex: "my-shop.myshopify.com")
    |               ⚠️ NE PAS inclure "https://".
    | api_key     : clé publique de l'application (App credentials).
    | api_secret  : secret partagé. À stocker dans le .env, jamais en dur.
    | access_token: token d'accès offline (Admin API > Install app).
    | webhook_secret: HMAC secret pour vérifier les webhooks entrants.
    |
    | Tous ces secrets DOIVENT provenir de variables d'environnement.
    | Ne jamais committer un secret réel dans Git.
    |
    */

    'shop_domain'     => env('SHOPIFY_SHOP_DOMAIN'),
    'api_key'         => env('SHOPIFY_API_KEY'),
    'api_secret'      => env('SHOPIFY_API_SECRET'),
    'access_token'    => env('SHOPIFY_ACCESS_TOKEN'),
    'webhook_secret'  => env('SHOPIFY_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    |
    | api_version : version de l'Admin API utilisée. Voir https://shopify.dev
    |               pour la liste des versions stables. Format YYYY-MM.
    |
    */

    'api_version' => env('SHOPIFY_API_VERSION', '2025-01'),

    /*
    |--------------------------------------------------------------------------
    | Base URL de l'API
    |--------------------------------------------------------------------------
    |
    | Construite automatiquement à partir du shop_domain et de api_version.
    | Format : https://{shop}/admin/api/{version}/
    |
    */

    'api_base_url' => env(
        'SHOPIFY_API_BASE_URL',
        sprintf(
            'https://%s/admin/api/%s/',
            env('SHOPIFY_SHOP_DOMAIN', ''),
            env('SHOPIFY_API_VERSION', '2025-01')
        )
    ),

    /*
    |--------------------------------------------------------------------------
    | Timeouts HTTP
    |--------------------------------------------------------------------------
    |
    | connect_timeout : délai max pour établir la connexion TCP (secondes).
    | timeout         : délai max total de la requête (secondes).
    |
    | Shopify garantit des réponses < 5s en moyenne ; au-delà, on considère
    | que la requête est perdue et on retry.
    |
    */

    'connect_timeout' => (int) env('SHOPIFY_CONNECT_TIMEOUT', 5),
    'timeout'         => (int) env('SHOPIFY_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Policy
    |--------------------------------------------------------------------------
    |
    | Stratégie de retry exponentiel pour les appels Admin API sortants.
    | On retry uniquement sur :
    |   - erreurs réseau (timeout, DNS, connexion refusée)
    |   - HTTP 5xx (sauf 501 Not Implemented)
    |   - HTTP 429 (rate limit) en respectant Retry-After
    |
    | On ne retry JAMAIS sur 4xx (erreurs client).
    |
    | max_attempts : nombre total de tentatives (1 = pas de retry).
    | initial_delay: délai initial avant le 1er retry (ms).
    | multiplier   : coefficient multiplicateur entre chaque retry.
    | max_delay    : plafond du délai entre deux retries (ms).
    |
    */

    'retry' => [
        'max_attempts'  => (int) env('SHOPIFY_RETRY_MAX_ATTEMPTS', 5),
        'initial_delay' => (int) env('SHOPIFY_RETRY_INITIAL_DELAY', 500),
        'multiplier'    => (float) env('SHOPIFY_RETRY_MULTIPLIER', 2.0),
        'max_delay'     => (int) env('SHOPIFY_RETRY_MAX_DELAY', 30000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Limites nommées pour les routes Shopify. Override des valeurs par défaut.
    | Voir App\Providers\AppServiceProvider pour les définitions.
    |
    | webhook : limite pour les webhooks entrants Shopify (par IP).
    | api     : limite pour les endpoints Admin API Shopify sortants (par bucket).
    | public  : limite pour les endpoints publics de l'app (par IP).
    |
    */

    'rate_limits' => [
        'webhook' => [
            'per_minute' => (int) env('SHOPIFY_RATE_LIMIT_WEBHOOK', 120),
        ],
        'api' => [
            'per_minute' => (int) env('SHOPIFY_RATE_LIMIT_API', 40),
        ],
        'public' => [
            'per_minute' => (int) env('SHOPIFY_RATE_LIMIT_PUBLIC', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Replay Attack Protection
    |--------------------------------------------------------------------------
    |
    | enabled  : active la protection contre les webhooks dupliqués via le
    |            header X-Shopify-Webhook-Id.
    | ttl_days : durée de conservation d'un webhook_id dans la table.
    |            Au-delà, on autorise un nouveau traitement du même ID
    |            (cas rare : retry après plusieurs jours).
    |
    */

    'replay_protection' => [
        'enabled'  => (bool) env('SHOPIFY_REPLAY_PROTECTION', true),
        'ttl_days' => (int) env('SHOPIFY_REPLAY_TTL_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vérification IP Shopify
    |--------------------------------------------------------------------------
    |
    | enabled    : active la vérification que la requête provient bien d'une
    |              IP Shopify officielle. Listes d'IPs Shopify :
    |              https://shopify.dev/docs/api/webhooks#known-ip-addresses
    | allow_ipv6 : autorise aussi les IPv6 officielles Shopify.
    | strict     : en mode strict, rejette immédiatement si l'IP n'est pas
    |              Shopify. En mode permissif, on log un warning mais on
    |              accepte la requête (HMAC reste la sécurité principale).
    |
    */

    'ip_verification' => [
        'enabled'    => (bool) env('SHOPIFY_IP_VERIFICATION', false),
        'allow_ipv6' => (bool) env('SHOPIFY_IP_VERIFICATION_IPV6', true),
        'strict'     => (bool) env('SHOPIFY_IP_VERIFICATION_STRICT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | channel      : canal Monolog dédié (config/logging.php).
    | log_payload  : log le payload brut dans les fichiers de debug.
    |                ⚠️ DÉSACTIVER EN PRODUCTION (données RGPD sensibles).
    | mask_fields  : champs à masquer automatiquement dans tous les logs.
    |
    */

    'logging' => [
        'channel'     => env('SHOPIFY_LOG_CHANNEL', 'shopify'),
        'log_payload' => (bool) env('SHOPIFY_LOG_PAYLOAD', false),
        'mask_fields' => [
            'email',
            'customer_email',
            'phone',
            'password',
            'token',
            'secret',
            'authorization',
            'access_token',
            'x_shopify_access_token',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks activés
    |--------------------------------------------------------------------------
    |
    | Liste blanche des topics que l'application traite.
    | Tout autre topic sera rejeté par le contrôleur (sécurité positive).
    |
    | Documentation : https://shopify.dev/docs/api/admin-rest/webhooks
    |
    */

    'webhook_topics' => [
        'orders/create',
        'orders/paid',
        'orders/updated',
        'orders/cancelled',
        'orders/delete',
        'refunds/create',
        'app/uninstalled',
    ],

    /*
    |--------------------------------------------------------------------------
    | Topics déclencheurs de génération QR
    |--------------------------------------------------------------------------
    |
    | Liste des topics pour lesquels on génère un QR Code automatiquement.
    | Typiquement : orders/paid uniquement.
    | orders/create est en trop tôt dans le tunnel de conversion (panier
    | abandonné possible).
    |
    */

    'qr_trigger_topics' => [
        'orders/paid',
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoints Admin API utilisés
    |--------------------------------------------------------------------------
    |
    | Préfixes d'endpoints Shopify que l'application est susceptible d'appeler.
    | Utilisé pour :
    |  - préfixer les logs avec le contexte de l'endpoint appelé
    |  - générer des metrics par endpoint (latence, taux d'erreur)
    |  - faciliter le refactoring si Shopify déprécie un endpoint
    |
    */

    'api_endpoints' => [
        'products'   => 'products.json',
        'customers'  => 'customers.json',
        'orders'     => 'orders.json',
        'inventory'  => 'inventory_levels.json',
        'fulfillment'=> 'fulfillments.json',
        'webhooks'   => 'webhooks.json',
    ],

];