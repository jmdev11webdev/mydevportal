const CACHE_NAME = 'mydevportal-v1';
const APP_SHELL = [
    '/',
    '/offline.html',
    '/manifest.webmanifest',
    '/favicon.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE_NAME)
            .then((cache) => cache.addAll(APP_SHELL))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => key !== CACHE_NAME)
                        .map((key) => caches.delete(key)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    // Let Inertia handle its own JSON navigation requests.
    if (request.headers.get('X-Inertia')) {
        return;
    }

    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(request).then(
                (cached) =>
                    cached ??
                    fetch(request).then((response) => {
                        const copy = response.clone();
                        caches
                            .open(CACHE_NAME)
                            .then((cache) => cache.put(request, copy));

                        return response;
                    }),
            ),
        );

        return;
    }

    event.respondWith(
        fetch(request)
            .then((response) => {
                if (response.ok && response.type === 'basic') {
                    const copy = response.clone();
                    caches
                        .open(CACHE_NAME)
                        .then((cache) => cache.put(request, copy));
                }

                return response;
            })
            .catch(() =>
                caches.match(request).then((cached) => {
                    if (cached) {
                        return cached;
                    }

                    if (request.mode === 'navigate') {
                        return caches.match('/offline.html');
                    }

                    return Response.error();
                }),
            ),
    );
});
