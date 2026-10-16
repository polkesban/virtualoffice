const SHELL_CACHE = 'polkesban-radio-shell-v2';
const AUDIO_CACHE = 'polkesban-radio-audio-v2';

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((k) => k !== SHELL_CACHE && k !== AUDIO_CACHE).map((k) => caches.delete(k))
      );
    }).then(() => self.clients.claim())
  );
});

function parseRangeHeader(rangeHeader, size) {
  const match = /bytes=(\d*)-(\d*)/i.exec(String(rangeHeader || ''));
  if (!match) {
    return null;
  }

  let start = match[1] === '' ? NaN : Number(match[1]);
  let end = match[2] === '' ? NaN : Number(match[2]);
  if (Number.isNaN(start) && Number.isNaN(end)) {
    return null;
  }

  if (Number.isNaN(start)) {
    const suffixLength = Number.isFinite(end) ? end : 0;
    start = Math.max(size - suffixLength, 0);
    end = size - 1;
  } else {
    if (!Number.isFinite(start) || start < 0) {
      start = 0;
    }
    if (Number.isNaN(end) || !Number.isFinite(end) || end >= size) {
      end = size - 1;
    }
  }

  if (start > end || start >= size) {
    return null;
  }

  return { start, end };
}

async function buildRangeResponseFromCache(cachedResponse, rangeHeader) {
  const blob = await cachedResponse.blob();
  const range = parseRangeHeader(rangeHeader, blob.size);
  if (!range) {
    return new Response(null, {
      status: 416,
      headers: {
        'Content-Range': `bytes */${blob.size}`,
        'Accept-Ranges': 'bytes',
      },
    });
  }

  const slicedBlob = blob.slice(range.start, range.end + 1, cachedResponse.headers.get('Content-Type') || 'audio/mpeg');
  const headers = new Headers(cachedResponse.headers);
  headers.set('Content-Length', String(slicedBlob.size));
  headers.set('Content-Range', `bytes ${range.start}-${range.end}/${blob.size}`);
  headers.set('Accept-Ranges', 'bytes');
  return new Response(slicedBlob, {
    status: 206,
    statusText: 'Partial Content',
    headers,
  });
}

async function handleAudioRequest(request) {
  const cache = await caches.open(AUDIO_CACHE);
  const cacheKey = request.url;
  const rangeHeader = request.headers.get('range');
  const cachedResponse = await cache.match(cacheKey);
  if (cachedResponse) {
    return rangeHeader ? buildRangeResponseFromCache(cachedResponse, rangeHeader) : cachedResponse;
  }

  const networkResponse = await fetch(request);
  if (networkResponse && networkResponse.ok && !rangeHeader) {
    cache.put(cacheKey, networkResponse.clone()).catch(() => {});
  }
  return networkResponse;
}

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (!request || request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);

  // Explicitly ignore any graduation photos, unit events, or storage assets outside suno
  if (url.pathname.includes('/wisudawan-photos/') ||
      url.pathname.startsWith('/unit/') ||
      url.pathname.startsWith('/admin/') ||
      url.pathname.startsWith('/images/wisuda-slide/')) {
    return;
  }

  // 1. Intercept suno-stream, storage/suno_cache, podcast-stream, storage/podcast_cache
  if (url.pathname.includes('/api/suno-stream/') || url.pathname.includes('/storage/suno_cache/') ||
      url.pathname.includes('/api/podcast-stream/') || url.pathname.includes('/storage/podcast_cache/') ||
      url.hostname.includes('suno.ai') || url.hostname.includes('googleusercontent.com')) {
    event.respondWith(handleAudioRequest(request));
    return;
  }

  // 2. Only intercept player shell page or suno player assets
  const isEmbedPlayerPage = url.pathname === '/suno-player/embed';
  const isSunoSpecific = url.pathname.startsWith('/suno-player/');

  if (isEmbedPlayerPage || isSunoSpecific) {
    event.respondWith(
      caches.match(request).then((cachedResponse) => {
        if (cachedResponse) {
          fetch(request).then((networkResponse) => {
            if (networkResponse && networkResponse.ok) {
              caches.open(SHELL_CACHE).then((cache) => cache.put(request, networkResponse));
            }
          }).catch(() => {});
          return cachedResponse;
        }
        return fetch(request).then((networkResponse) => {
          if (networkResponse && networkResponse.ok) {
            const cacheCopy = networkResponse.clone();
            caches.open(SHELL_CACHE).then((cache) => cache.put(request, cacheCopy));
          }
          return networkResponse;
        }).catch(() => {
          if (request.mode === 'navigate') {
            return caches.match('/suno-player/embed').then((r) => r || Response.error());
          }
          return Response.error();
        });
      })
    );
  }
});
