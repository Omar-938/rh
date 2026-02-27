/**
 * SimpliRH Service Worker
 * Stratégie : Network-first pour les pages, Cache-first pour les assets statiques
 */

const CACHE_NAME = 'simplirh-v1';
const STATIC_ASSETS = [
    '/manifest.json',
    '/offline.html',
];

// Installation : mise en cache des assets statiques
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch(() => {
                // Ignorer les erreurs d'assets manquants en dev
            });
        })
    );
    self.skipWaiting();
});

// Activation : nettoyage des anciens caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

// Interception des requêtes
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Ne pas intercepter les requêtes non-GET, les APIs, les requêtes Inertia
    if (
        request.method !== 'GET' ||
        url.pathname.startsWith('/api/') ||
        request.headers.get('X-Inertia')
    ) {
        return;
    }

    // Assets statiques (JS, CSS, images, fonts) → Cache-first
    if (
        url.pathname.match(/\.(js|css|png|jpg|jpeg|svg|ico|woff|woff2|ttf)$/) ||
        url.pathname.startsWith('/build/')
    ) {
        event.respondWith(
            caches.match(request).then((cached) => {
                return cached || fetch(request).then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Pages HTML → Network-first avec fallback offline
    event.respondWith(
        fetch(request)
            .then((response) => {
                return response;
            })
            .catch(() => {
                return caches.match('/offline.html').then((cached) => {
                    return cached || new Response(
                        '<!DOCTYPE html><html lang="fr"><body><h1>Hors ligne</h1><p>Vérifiez votre connexion.</p></body></html>',
                        { headers: { 'Content-Type': 'text/html' } }
                    );
                });
            })
    );
});

// Écoute des messages (ex: skip waiting depuis l'UI)
self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
