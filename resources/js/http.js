export const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export const fetchPartial = async (url, signal) => {
    const response = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
        signal,
    });

    if (!response.ok) {
        throw new Error(`Request failed with status ${response.status}`);
    }

    return response.text();
};
