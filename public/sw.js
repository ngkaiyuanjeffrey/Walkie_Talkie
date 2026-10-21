const CACHE = 'walkie-shell-v1';
const SHELL = ['./', './public/assets/css/app.css', './public/assets/js/app.js', './public/assets/js/ptt.js', './public/assets/js/webrtc.js', './public/assets/js/pwa.js', './public/manifest.webmanifest'];
self.addEventListener('install', event => event.waitUntil(caches.open(CACHE).then(cache => cache.addAll(SHELL))));
self.addEventListener('activate', event => event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key !== CACHE).map(key => caches.delete(key))))));
self.addEventListener('fetch', event => {
    if (new URL(event.request.url).searchParams.has('api')) return;
    event.respondWith(caches.match(event.request).then(cached => cached || fetch(event.request).then(response => { const copy = response.clone(); caches.open(CACHE).then(cache => cache.put(event.request, copy)); return response; })));
});
