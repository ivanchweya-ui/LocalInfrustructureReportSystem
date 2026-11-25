const cacheName = 'local-infrastructure-shell-v1';

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(cacheName).then(cache => cache.add('./offline.html'))
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.filter(key => key.startsWith('local-infrastructure-shell-') && key !== cacheName)
        .map(key => caches.delete(key))
    ))
  );
});

self.addEventListener('fetch', event => {
  const request = event.request;
  const url = new URL(request.url);
  if (request.method !== 'GET' || url.origin !== self.location.origin || url.pathname.includes('/php/')) return;
  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match('./offline.html')));
  }
});
