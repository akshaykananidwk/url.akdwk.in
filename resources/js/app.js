import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

/* ------------------------------------------------------------------ theme */
// Light/dark: explicit user choice wins, otherwise follow the system.
function applyTheme() {
    const stored = localStorage.getItem('theme');
    const dark = stored === 'dark' || (stored !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', dark);
}
applyTheme();
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', applyTheme);
window.toggleTheme = () => {
    const isDark = document.documentElement.classList.contains('dark');
    localStorage.setItem('theme', isDark ? 'light' : 'dark');
    applyTheme();
    document.dispatchEvent(new CustomEvent('theme-changed'));
};

/* ------------------------------------------------------------------ toasts */
window.toast = (message, type = 'success') => {
    document.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
};

/* --------------------------------------------------------------- clipboard */
window.copyText = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        window.toast(window.__t?.copied || 'Copied to clipboard');
    } catch {
        const el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        el.remove();
        window.toast(window.__t?.copied || 'Copied to clipboard');
    }
};

/* ------------------------------------------------------------------ charts */
// Shared chart factory with theme-aware colors; charts rebuild on theme change.
window.makeLineChart = (canvasId, labels, datasets, opts = {}) => {
    const el = document.getElementById(canvasId);
    if (!el) return null;

    const build = () => {
        const dark = document.documentElement.classList.contains('dark');
        const grid = dark ? 'rgba(148,163,184,.12)' : 'rgba(100,116,139,.12)';
        const tick = dark ? '#94a3b8' : '#64748b';
        if (el._chart) el._chart.destroy();
        el._chart = new Chart(el, {
            type: opts.type || 'line',
            data: {
                labels,
                datasets: datasets.map((d, i) => ({
                    tension: 0.35,
                    fill: opts.type !== 'bar',
                    borderWidth: 2,
                    pointRadius: labels.length > 40 ? 0 : 2,
                    borderColor: d.color || ['#6366f1', '#22c55e', '#f59e0b'][i % 3],
                    backgroundColor: (d.color || ['#6366f1', '#22c55e', '#f59e0b'][i % 3]) + (opts.type === 'bar' ? 'cc' : '22'),
                    ...d,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: datasets.length > 1, labels: { color: tick, boxWidth: 12 } } },
                scales: {
                    x: { grid: { color: grid }, ticks: { color: tick, maxTicksLimit: 8 } },
                    y: { grid: { color: grid }, ticks: { color: tick, precision: 0 }, beginAtZero: true },
                },
            },
        });
    };
    build();
    document.addEventListener('theme-changed', build);
    return el;
};

window.makeDoughnut = (canvasId, labels, values) => {
    const el = document.getElementById(canvasId);
    if (!el) return null;
    const palette = ['#6366f1', '#22c55e', '#f59e0b', '#ec4899', '#06b6d4', '#8b5cf6', '#f43f5e', '#84cc16', '#64748b', '#0ea5e9', '#d946ef', '#f97316'];
    const build = () => {
        const dark = document.documentElement.classList.contains('dark');
        if (el._chart) el._chart.destroy();
        el._chart = new Chart(el, {
            type: 'doughnut',
            data: { labels, datasets: [{ data: values, backgroundColor: palette, borderWidth: 0 }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: { legend: { position: 'bottom', labels: { color: dark ? '#94a3b8' : '#64748b', boxWidth: 10, padding: 12 } } },
            },
        });
    };
    build();
    document.addEventListener('theme-changed', build);
    return el;
};

/* --------------------------------------------------------------------- PWA */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

Alpine.start();
