/* =========================================================
   VISRS Service Worker
   =========================================================
   Handles offline fallback. When a navigation request fails
   (no internet), serves /visrs/offline.php instead of the
   browser's default offline screen.

   Does NOT cache page content. Every successful request goes
   to the network. Only page navigations that fail get the
   offline fallback — all other requests (CSS, JS, images,
   AJAX) go straight to the network and let the browser handle
   failures natively, keeping the console clean.
   ========================================================= */

'use strict';

var OFFLINE_URL = '/visrs/offline.php';

var CACHE_NAME = 'visrs-offline-v3';

var PRECACHE_URLS = [
    OFFLINE_URL
];


/* ---------- Install: precache the offline page ---------- */

self.addEventListener('install', function (event) {

    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function (cache) {
                return cache.addAll(PRECACHE_URLS);
            })
            .then(function () {
                /* Activate immediately, don't wait for old tabs to close */
                return self.skipWaiting();
            })
    );

});


/* ---------- Activate: clean up old caches ---------- */

self.addEventListener('activate', function (event) {

    event.waitUntil(
        caches.keys()
            .then(function (keys) {
                return Promise.all(
                    keys
                        .filter(function (key) { return key !== CACHE_NAME; })
                        .map(function (key) { return caches.delete(key); })
                );
            })
            .then(function () {
                /* Take control of any open tabs immediately */
                return self.clients.claim();
            })
    );

});


/* ---------- Fetch: network first, offline fallback for navigations ---------- */

self.addEventListener('fetch', function (event) {

    var request = event.request;

    /* Only handle GET requests */
    if (request.method !== 'GET') return;

    /* Only handle same-origin requests */
    var url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    /* Only handle requests within the VISRS scope */
    if (!url.pathname.startsWith('/visrs/')) return;

    /*
     * Only intercept page navigations.
     *
     * Everything else (stylesheets, scripts, images, AJAX,
     * fonts) goes straight to the network. If those fail,
     * the browser handles it the way it always would — no
     * artificial 504s cluttering the console.
     */
    if (request.mode !== 'navigate') return;

    event.respondWith(

        fetch(request)
            .catch(function () {

                /* Network failed on a page navigation —
                   serve the offline page from cache */
                return caches.match(OFFLINE_URL);
            })
    );

});