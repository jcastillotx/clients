// Basic service worker for offline shell caching
const CACHE_NAME = 'client-portal-v1';
const CORE_ASSETS = [
  '/',
  '/dashboard',
  '/manifest.webmanifest',
  '/favicon.ico',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(CORE_ASSETS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.map((k) => (k === CACHE_NAME ? null : caches.delete(k)))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  event.respondWith(
    caches.match(req).then((cached) => {
      if (cached) return cached;
      return fetch(req).then((resp) => {
        // Cache same-origin navigations + static assets
        try {
          const url = new URL(req.url);
          if (url.origin === self.location.origin && (req.destination === 'document' || req.destination === 'script' || req.destination === 'style')) {
            const copy = resp.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          }
        } catch (e) {}
        return resp;
      }).catch(() => cached || new Response('Offline', { status: 503 }));
    })
  );
});

