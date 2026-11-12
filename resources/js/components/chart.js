export default (config) => ({
    instance: null,

    async init() {
        const { default: Chart } = await import('chart.js/auto');
        const styles = getComputedStyle(document.documentElement);
        const brand = styles.getPropertyValue('--color-brand').trim() || '#88c040';
        const line = styles.getPropertyValue('--color-line').trim() || '#2c2c35';
        const mist = styles.getPropertyValue('--color-mist').trim() || '#9ba1a6';

        this.instance = new Chart(this.$refs.canvas, {
            type: config.type ?? 'line',
            data: {
                labels: config.labels,
                datasets: [
                    {
                        label: config.label,
                        data: config.values,
                        borderColor: brand,
                        backgroundColor: `color-mix(in srgb, ${brand} 22%, transparent)`,
                        borderWidth: 2,
                        borderRadius: 3,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                    },
                ],
            },
            options: {
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.dataset.label}: ${context.formattedValue}${config.suffix ?? ''}`,
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: mist, maxTicksLimit: 8 } },
                    y: { beginAtZero: true, grid: { color: line }, ticks: { color: mist, precision: 0 } },
                },
            },
        });
    },

    destroy() {
        this.instance?.destroy();
    },
});
