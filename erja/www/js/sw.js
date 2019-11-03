addEventListener('fetch', event => {
    event.respondWith(
        fetch(event.request).catch(() => new Response('You are offline'))
    );
});