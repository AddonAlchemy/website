export default (endpoint) => ({
    term: '',
    results: [],
    more: null,
    open: false,
    loading: false,
    active: -1,
    controller: null,
    timer: null,

    onInput() {
        clearTimeout(this.timer);

        if (this.term.trim().length < 2) {
            this.results = [];
            this.open = false;

            return;
        }

        this.timer = setTimeout(() => this.search(), 180);
    },

    async search() {
        this.controller?.abort();
        this.controller = new AbortController();
        this.loading = true;

        try {
            const response = await fetch(`${endpoint}?q=${encodeURIComponent(this.term.trim())}`, {
                headers: { Accept: 'application/json' },
                signal: this.controller.signal,
            });
            const payload = await response.json();

            this.results = payload.results ?? [];
            this.more = payload.more ?? null;
            this.active = -1;
            this.open = true;
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.results = [];
            }
        } finally {
            this.loading = false;
        }
    },

    move(offset) {
        if (!this.results.length) {
            return;
        }

        this.active = (this.active + offset + this.results.length) % this.results.length;
    },

    go(event) {
        if (this.active >= 0 && this.results[this.active]) {
            event.preventDefault();
            window.location.href = this.results[this.active].url;
        }
    },

    close() {
        this.open = false;
    },
});
