/* Service worker: cache-first for built assets, network-first for pages with
   an offline fallback. Never intercepts short-link redirects (opaque
   navigations to unknown paths pass straight through to the network). */
const CACHE = 'shortl-v1';
const OFFLINE_URL = '/offline';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll([OFFLINE_URL])).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    if (event.request.method !== 'GET' || url.origin !== location.origin) return;

    // Built assets: cache-first (they are content-hashed).
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(event.request).then(
                (hit) => hit || fetch(event.request).then((res) => {
                    const copy = res.clone();
                    caches.open(CACHE).then((c) => c.put(event.request, copy));
                    return res;
                })
            )
        );
        return;
    }

    // App pages: network-first with offline fallback. Short links are plain
    // redirects, so we only handle known app paths.
    const appPaths = ['/dashboard', '/links', '/stats', '/qr', '/bio', '/account', '/spaces', '/domains', '/pixels', '/tools', '/billing', '/team', '/developers', '/affiliate', '/'];
    if (event.request.mode === 'navigate' && appPaths.some((p) => url.pathname === p || url.pathname.startsWith(p + '/'))) {
        event.respondWith(
            fetch(event.request).catch(() => caches.match(OFFLINE_URL))
        );
    }
});

// Web push: show a notification when the server pushes (payloadless by default).
self.addEventListener('push', (event) => {
    const body = event.data ? event.data.text() : '';
    event.waitUntil(
        self.registration.showNotification('Notification', {
            body: body || 'You have a new update',
            tag: 'app-notification',
        })
    );
});

// Focus an existing tab (or open one) when a notification is clicked.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = self.location.origin + '/';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (const client of clients) {
                if ('focus' in client) {
                    client.navigate(target);
                    return client.focus();
                }
            }
            if (self.clients.openWindow) return self.clients.openWindow(target);
        })
    );
});
