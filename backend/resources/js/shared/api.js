/**
 * Minimal API client for the back-office SPAs (kitchen now, admin in PHASE 6).
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

export function createApi({ baseUrl = '/api', locale, onUnauthorized } = {}) {
    async function request(method, path, body) {
        const headers = { Accept: 'application/json', 'Accept-Language': locale() };
        const token = getToken();
        if (token) headers.Authorization = `Bearer ${token}`;
        if (body !== undefined) headers['Content-Type'] = 'application/json';

        let response;
        try {
            response = await fetch(baseUrl + path, {
                method,
                headers,
                body: body === undefined ? undefined : JSON.stringify(body),
            });
        } catch {
            throw new ApiError('NETWORK');
        }

        const payload = await response.json().catch(() => null);
        if (response.ok && payload?.success) return payload;

        if (response.status === 401) onUnauthorized?.();
        throw new ApiError(payload?.error?.code ?? 'UNKNOWN', {
            status: response.status,
            fields: payload?.error?.fields ?? null,
        });
    }

    return {
        get: (path) => request('GET', path),
        post: (path, body = {}) => request('POST', path, body),
        put: (path, body = {}) => request('PUT', path, body),
    };
}
