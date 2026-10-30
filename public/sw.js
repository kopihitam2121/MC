'use strict';

const STATIC_CACHE = 'money-changer-static-v2';
const APP_SCOPE = new URL('./', self.location.href);
const OFFLINE_URL = new URL('offline.html', APP_SCOPE).href;
const PRECACHE_URLS = [
  OFFLINE_URL,
  new URL('assets/css/app.css', APP_SCOPE).href,
  new URL('assets/js/app.js', APP_SCOPE).href,
  new URL('assets/icons/pwa-192.png', APP_SCOPE).href,
  new URL('assets/icons/pwa-512.png', APP_SCOPE).href,
  new URL('manifest.webmanifest', APP_SCOPE).href
];

self.addEventListener('install', event => {
  event.waitUntil(caches.open(STATIC_CACHE).then(cache => cache.addAll(PRECACHE_URLS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', event => {
  event.waitUntil(caches.keys().then(keys => Promise.all(
    keys.filter(key => key.startsWith('money-changer-static-') && key !== STATIC_CACHE).map(key => caches.delete(key))
  )).then(() => self.clients.claim()));
});

self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  if (request.mode === 'navigate') {
    // Never cache authenticated PHP pages or transaction data. Only use a safe static fallback offline.
    event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
    return;
  }

  const isStaticAsset = url.pathname.includes('/assets/') || url.pathname.endsWith('/manifest.webmanifest');
  if (!isStaticAsset) return;

  event.respondWith(caches.match(request).then(cached => {
    const fresh = fetch(request).then(response => {
      if (response && response.ok && response.type === 'basic') {
        const copy = response.clone();
        caches.open(STATIC_CACHE).then(cache => cache.put(request, copy));
      }
      return response;
    });
    return cached || fresh.catch(() => Response.error());
  }));
});
