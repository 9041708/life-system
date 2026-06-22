const CACHE_NAME = 'ssjizhang-cache-v3';
const STATIC_ASSETS = [
  '/assets/css/app.css',
  '/assets/vendor/bootstrap/bootstrap.min.css',
  '/assets/vendor/bootstrap/bootstrap.bundle.min.js',
  '/assets/vendor/choices/choices.min.css',
  '/assets/vendor/choices/choices.min.js',
  '/assets/js/qrcode.min.js'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache =>
      Promise.allSettled(STATIC_ASSETS.map(url => cache.add(url).catch(() => null)))
    )
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;
  const url = new URL(event.request.url);
  if (event.request.mode === 'navigate') return;
  if (url.pathname.endsWith('.php')) return;

  if (url.pathname.startsWith('/assets/') || url.pathname.startsWith('/uploads/')) {
    event.respondWith(
      caches.match(event.request).then(cached => {
        if (cached) return cached;
        return fetch(event.request).then(response => {
          if (response && response.status === 200) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
          }
          return response;
        }).catch(() => cached || Response.error());
      })
    );
  }
});
