import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import catalog from './components/catalog';
import chart from './components/chart';
import helpCenter from './components/help-center';
import liveSearch from './components/live-search';
import { csrfToken } from './http';

Alpine.plugin(collapse);

Alpine.store('toasts', {
    items: [],
    push(message, type = 'success') {
        if (!message) {
            return;
        }

        const id = Date.now() + Math.random();
        this.items.push({ id, message, type });
        setTimeout(() => this.dismiss(id), 5000);
    },
    dismiss(id) {
        this.items = this.items.filter((item) => item.id !== id);
    },
});

Alpine.data('catalog', catalog);
Alpine.data('chart', chart);
Alpine.data('helpCenter', helpCenter);
Alpine.data('liveSearch', liveSearch);

Alpine.data('dismissible', (storageKey, delay = 0) => ({
    visible: false,
    init() {
        if (localStorage.getItem(storageKey) !== '1') {
            setTimeout(() => (this.visible = true), delay);
        }
    },
    dismiss() {
        localStorage.setItem(storageKey, '1');
        this.visible = false;
    },
    reopen() {
        localStorage.removeItem(storageKey);
        this.visible = true;
    },
}));

Alpine.data('clipboard', (value) => ({
    copied: false,
    async copy() {
        await navigator.clipboard.writeText(value);
        this.copied = true;
        setTimeout(() => (this.copied = false), 1800);
    },
}));

Alpine.data('favorite', (url, initial) => ({
    active: initial,
    busy: false,
    async toggle() {
        if (this.busy) {
            return;
        }

        this.busy = true;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });

            if (response.status === 401) {
                window.location.href = '/login';

                return;
            }

            if (response.ok) {
                this.active = (await response.json()).favorited;
                Alpine.store('toasts').push(this.active ? 'Zu deinen Favoriten hinzugefügt.' : 'Aus den Favoriten entfernt.');
            }
        } finally {
            this.busy = false;
        }
    },
}));

Alpine.data('gallery', (images) => ({
    images,
    index: null,
    open(index) {
        this.index = index;
    },
    close() {
        this.index = null;
    },
    step(offset) {
        this.index = (this.index + offset + this.images.length) % this.images.length;
    },
}));

document.addEventListener('submit', (event) => {
    const message = event.target.dataset?.confirm;

    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

window.Alpine = Alpine;
Alpine.start();
