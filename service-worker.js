const CACHE_NAME = 'strively-v1';
const urlsToCache = [
  '/Strively/',
  '/Strively/index.php',
  '/Strively/assets/css/style.css',
  '/Strively/images/logo.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
  );
});
