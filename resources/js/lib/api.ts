// Tiny JSON/multipart fetch helper that carries the session cookie + CSRF token.
// Used by the chat UI for its read/write endpoints (messages, workspaces).
//
// We send the XSRF-TOKEN *cookie* (which Laravel refreshes on every response) as
// X-XSRF-TOKEN, mirroring Inertia's axios. Reading a static <meta> token instead
// breaks after login, because Fortify regenerates the session token and Inertia
// never does a full page reload to refresh the meta tag.

export function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function request<T>(url: string, options: RequestInit = {}): Promise<T> {
    const res = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers ?? {}),
        },
        ...options,
    });

    if (!res.ok) {
        throw new Error(`Request failed: ${res.status}`);
    }

    return res.status === 204 ? (undefined as T) : ((await res.json()) as T);
}

async function postForm<T>(url: string, form: FormData): Promise<T> {
    // No Content-Type header: the browser sets the multipart boundary itself.
    const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: form,
    });

    if (!res.ok) {
        throw new Error(`Request failed: ${res.status}`);
    }

    return (await res.json()) as T;
}

export const api = {
    get: <T>(url: string) => request<T>(url),
    post: <T>(url: string, body?: unknown) =>
        request<T>(url, { method: 'POST', body: JSON.stringify(body ?? {}) }),
    postForm,
};
