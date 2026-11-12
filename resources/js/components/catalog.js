import { fetchPartial } from '../http';

export default () => ({
    loading: false,
    filtersOpen: false,
    tag: '',
    controller: null,
    timer: null,

    init() {
        this.tag = this.$refs.tag?.value ?? '';

        this.$refs.results.addEventListener('click', (event) => {
            const link = event.target.closest('nav[data-pagination] a');

            if (link) {
                event.preventDefault();
                this.load(link.href, true);
            }
        });

        window.addEventListener('popstate', () => this.load(window.location.href));
    },

    queue(delay = 0) {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.load(this.url()), delay);
    },

    toggleTag(slug) {
        this.tag = this.tag === slug ? '' : slug;
        this.$nextTick(() => this.queue());
    },

    reset() {
        this.$refs.form.querySelectorAll('input, select').forEach((field) => {
            if (field.type === 'radio') {
                field.checked = field.value === '';
            } else if (field.type !== 'hidden') {
                field.value = '';
            }
        });
        this.tag = '';
        this.$nextTick(() => this.queue());
    },

    url() {
        const params = new URLSearchParams();

        new FormData(this.$refs.form).forEach((value, key) => {
            if (value !== '' && !(key === 'sort' && value === 'featured')) {
                params.set(key, value);
            }
        });

        const query = params.toString();

        return this.$refs.form.action + (query ? `?${query}` : '');
    },

    async load(url, scroll = false) {
        this.controller?.abort();
        this.controller = new AbortController();
        this.loading = true;

        try {
            this.$refs.results.innerHTML = await fetchPartial(url, this.controller.signal);
            window.history.replaceState({}, '', url);

            if (scroll) {
                this.$refs.results.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.href = url;
            }
        } finally {
            this.loading = false;
        }
    },
});
