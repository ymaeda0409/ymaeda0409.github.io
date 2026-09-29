/**
 * Minimal API client for the back-office SPAs (kitchen board and admin).
 * Sends Accept-Language + bearer token and turns error envelopes into ApiError(code).
 */
export class ApiError extends Error {
    constructor(code, { status = null, fields = null } = {}) {
        super(code);
        this.code = code;
        this.status = status;
        this.fields = fields;
    }
}

const TOKEN_KEY = 'bento.token';

export function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token) {
    if (token) localStorage.setItem(TOKEN_KEY, token);
    else localStorage.removeItem(TOKEN_KEY);
}

/** `{ a: 1, b: null, c: [x, y] }` → `?a=1&c[]=x&c[]=y` (empty values are skipped). */
export function queryString(params = {}) {
    const search = new URLSearchParams();
    for (const [key, value] of Object.entries(params)) {
        if (value === null || value === undefined || value === '') continue;
        if (Array.isArray(value)) value.forEach((v) => search.append(`${key}[]`, v));
        else search.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value);
    }
    const text = search.toString();
    return text ? `?${text}` : '';
}

export function createApi({ baseUrl = '/api', locale, onUnauthorized } = {}) {
    function headers(extra = {}) {
        const h = { Accept: 'application/json', 'Accept-Language': locale(), ...extra };
        const token = getToken();
        if (token) h.Authorization = `Bearer ${token}`;
        return h;
    }

    async function send(method, path, body) {
        try {
            return await fetch(baseUrl + path, {
                method,
                headers: headers(body === undefined ? {} : { 'Content-Type': 'application/json' }),
                body: body === undefined ? undefined : JSON.stringify(body),
            });
        } catch {
            throw new ApiError('NETWORK');
        }
    }

    async function fail(response) {
        const payload = await response.json().catch(() => null);
        if (response.status === 401) onUnauthorized?.();
        return new ApiError(payload?.error?.code ?? 'UNKNOWN', {
            status: response.status,
            fields: payload?.error?.fields ?? null,
        });
    }

    async function request(method, path, body) {
        const response = await send(method, path, body);
        if (!response.ok) throw await fail(response);
        const payload = await response.json().catch(() => null);
        if (payload?.success) return payload;
        throw new ApiError(payload?.error?.code ?? 'UNKNOWN', { status: response.status });
    }

    /** Authenticated file download (e.g. CSV export) → { blob, filename }. */
    async function download(path) {
        const response = await send('GET', path);
        if (!response.ok) throw await fail(response);
        const disposition = response.headers.get('Content-Disposition') ?? '';
        const filename = /filename="?([^";]+)"?/.exec(disposition)?.[1] ?? 'download';
        return { blob: await response.blob(), filename };
    }

    return {
        get: (path, params) => request('GET', path + queryString(params)),
        post: (path, body = {}) => request('POST', path, body),
        put: (path, body = {}) => request('PUT', path, body),
        delete: (path) => request('DELETE', path),
        download: (path, params) => download(path + queryString(params)),
    };
}
