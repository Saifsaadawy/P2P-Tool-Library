// ── API Helper ────────────────────────────────────
const _base = (typeof window.BASE_PATH !== 'undefined') ? window.BASE_PATH : '';

async function apiFetch(url, method = 'GET', body = null) {
    const fullUrl = url.startsWith('/') ? _base + url : url;
    const opts = {
        method,
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
    };
    if (body) opts.body = JSON.stringify(body);
    const res  = await fetch(fullUrl, opts);
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Request failed');
    return data;
}