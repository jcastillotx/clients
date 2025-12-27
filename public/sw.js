// PWA service worker: offline shell + runtime caching + basic push support
const CACHE_VERSION = 'v2';
const CACHE_NAME = `client-portal-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';

const CORE_ASSETS = [
  '/',
  '/dashboard',
  '/manifest.webmanifest',
  '/favicon.ico',
  '/icons/icon-192.svg',
  '/icons/icon-512.svg',
  '/icons/maskable-192.svg',
  '/icons/maskable-512.svg',
  OFFLINE_URL,
];

function isSameOrigin(url) {
  try {
    return new URL(url).origin === self.location.origin;
  } catch {
    return false;
  }
}

function isNavigationRequest(req) {
  return req.mode === 'navigate' || (req.destination === 'document' && req.method === 'GET');
}

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches
      .open(CACHE_NAME)
      .then((cache) => cache.addAll(CORE_ASSETS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) => Promise.all(keys.map((k) => (k === CACHE_NAME ? null : caches.delete(k)))))
      .then(() => self.clients.claim())
  );
});

// Network-first for navigations, stale-while-revalidate for static assets
self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  // Only cache same-origin (avoid caching CDNs/auth redirects unpredictably)
  if (!isSameOrigin(req.url)) return;

  if (isNavigationRequest(req)) {
    event.respondWith(
      fetch(req)
        .then((resp) => {
          // Cache navigations for offline revisit (best-effort)
          const copy = resp.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          return resp;
        })
        .catch(async () => {
          const cached = await caches.match(req);
          return cached || (await caches.match(OFFLINE_URL)) || new Response('Offline', { status: 503 });
        })
    );
    return;
  }

  // Static assets: cache-first + refresh
  event.respondWith(
    caches.match(req).then((cached) => {
      const fetchPromise = fetch(req)
        .then((resp) => {
          if (resp && resp.ok) {
            const copy = resp.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          }
          return resp;
        })
        .catch(() => cached);

      return cached || fetchPromise || new Response('Offline', { status: 503 });
    })
  );
});

// Push notifications (payload should be JSON: {title, body, url})
self.addEventListener('push', (event) => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch {
    data = { title: 'Notification', body: event.data ? event.data.text() : '' };
  }

  const title = data.title || 'Client Portal';
  const body = data.body || '';
  const url = data.url || '/dashboard';

  event.waitUntil(
    self.registration.showNotification(title, {
      body,
      icon: '/icons/icon-192.svg',
      badge: '/icons/icon-192.svg',
      data: { url },
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = event.notification?.data?.url || '/dashboard';
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientsArr) => {
      for (const client of clientsArr) {
        if (client.url.includes(url) && 'focus' in client) return client.focus();
      }
      if (self.clients.openWindow) return self.clients.openWindow(url);
    })
  );
});

