// Service Worker pour la PWA QR Shopify
const CACHE_NAME = 'qr-shopify-pwa-v1';
const ASSETS_TO_CACHE = [
  '/pwa/',
  '/pwa/scan',
  '/pwa/history',
  '/pwa/login',
  '/css/pwa.css',
  '/js/pwa.js',
  '/pwa/icon-192.png',
  '/pwa/icon-512.png'
];

// Installation - mise en cache des assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        return cache.addAll(ASSETS_TO_CACHE.map(url => new Request(url, { cache: 'reload' })));
      })
      .catch((error) => {
        console.log('Certaines ressources n\'ont pas pu être mises en cache:', error);
      })
  );
});

// Activation - nettoyage des anciens caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Interception des requêtes
self.addEventListener('fetch', (event) => {
  // Ne jamais mettre en cache les requêtes API (fraîcheur absolue requise)
  if (event.request.url.includes('/api/')) {
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        return response || fetch(event.request);
      })
  );
});

// Écouteur pour les messages (ex: mise à jour du cache)
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});