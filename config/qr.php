<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Durée de validité des QR Codes
    |--------------------------------------------------------------------------
    | Nombre de jours avant expiration d'un QR Code généré.
    | Configurable via QR_TTL_DAYS dans .env.
    */
    'ttl_days' => (int) env('QR_TTL_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Taille de l'image PNG générée
    |--------------------------------------------------------------------------
    | Dimensions en pixels du QR Code image (carré).
    */
    'size_px' => (int) env('QR_SIZE_PX', 400),

    /*
    |--------------------------------------------------------------------------
    | Clé secrète HMAC dédiée aux QR Codes
    |--------------------------------------------------------------------------
    | DOIT être distincte de APP_KEY.
    | Générer avec : openssl rand -base64 32
    */
    'secret' => env('APP_QR_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Rétention des validations
    |--------------------------------------------------------------------------
    | Nombre de jours avant suppression des anciennes validations
    | par le job de nettoyage hebdomadaire.
    */
    'cleanup_days' => (int) env('QR_CLEANUP_DAYS', 180),

    /*
    |--------------------------------------------------------------------------
    | Seuil d'alerte — QR expirés non scannés
    |--------------------------------------------------------------------------
    | Déclenche une alerte orange dans le dashboard admin
    | si le nombre de QR expirés non scannés dépasse ce seuil.
    */
    'alert_threshold' => (int) env('QR_ALERT_THRESHOLD', 10),

];
