const fallback = 'Das kann ich nicht sicher beantworten. Möchtest du dafür ein Support-Ticket erstellen?';

export default (topics, greeting) => ({
    open: false,
    started: false,
    draft: '',
    messages: [],

    toggle() {
        this.open = !this.open;

        if (this.open && !this.started) {
            this.started = true;
            this.messages.push({ from: 'desk', text: greeting });
        }
    },

    send() {
        const question = this.draft.trim();

        if (!question) {
            return;
        }

        this.messages.push({ from: 'visitor', text: question });
        this.draft = '';

        const needle = question.toLowerCase();
        const match = topics.find((topic) => topic.keywords.some((keyword) => needle.includes(keyword)));

        setTimeout(() => {
            this.messages.push(match ? { from: 'desk', text: match.answer } : { from: 'desk', text: fallback, escalate: true });
            this.$nextTick(() => {
                this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight;
            });
        }, 300);
    },
});
