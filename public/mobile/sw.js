const CACHE_NAME = 'ssj-mobile-cache-v1';
const ASSETS_TO_CACHE = [
  '/public/mobile/',
  '/public/mobile/index.php',
  '/public/mobile/app.css',
  '/public/mobile/app.js',
  '/public/mobile/manifest.webmanifest',
  '/public/mobile/icon.svg',
  '/public/mobile/offline.html'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS_TO_CACHE))
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys.map(key => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
          return null;
        })
      )
    )
  );
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') {
    return;
  }

  const url = new URL(event.request.url);

  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).catch(() => caches.match('/public/mobile/offline.html'))
    );
    return;
  }

  if (url.pathname.endsWith('/api.php') || url.pathname.endsWith('.php')) {
    event.respondWith(fetch(event.request));
    return;
  }

  event.respondWith(
    caches.match(event.request).then(cached => {
      if (cached) {
        return cached;
      }
      return fetch(event.request).then(response => {
        const clone = response.clone();
        caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
        return response;
      });
    }).catch(() => caches.match('/public/mobile/offline.html'))
  );
});