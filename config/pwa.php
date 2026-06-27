<?php

return [
    'name' => env('PWA_NAME', 'QR Shopify Scanner'),
    'short_name' => env('PWA_SHORT_NAME', 'QR Scanner'),
    'start_url' => env('PWA_START_URL', '/pwa/scan'),
    'display' => env('PWA_DISPLAY', 'standalone'),
    'background_color' => env('PWA_BACKGROUND_COLOR', '#0A2164'),
    'theme_color' => env('PWA_THEME_COLOR', '#1B56F5'),
    'orientation' => env('PWA_ORIENTATION', 'portrait'),
    'icons' => [
        [
            'src' => '/pwa/icon-192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
        ],
        [
            'src' => '/pwa/icon-512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
        ],
    ],
    'splash' => [
        '640x1136' => '/pwa/splash/640x1136.png',
        '750x1334' => '/pwa/splash/750x1334.png',
        '828x1792' => '/pwa/splash/828x1792.png',
        '1125x2436' => '/pwa/splash/1125x2436.png',
        '1242x2208' => '/pwa/splash/1242x2208.png',
        '1242x2688' => '/pwa/splash/1242x2688.png',
        '1536x2048' => '/pwa/splash/1536x2048.png',
        '1668x2224' => '/pwa/splash/1668x2224.png',
        '1668x2388' => '/pwa/splash/1668x2388.png',
        '2048x2732' => '/pwa/splash/2048x2732.png',
    ],
    'cache' => [
        'enabled' => env('PWA_CACHE_ENABLED', true),
        'strategy' => env('PWA_CACHE_STRATEGY', 'networkFirst'),
        'precache' => [
            '/pwa/',
            '/pwa/scan',
            '/pwa/history',
            '/pwa/login',
            '/pwa/result',
            '/css/pwa.css',
            '/js/pwa.js',
        ],
    ],
];