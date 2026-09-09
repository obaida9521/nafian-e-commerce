import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import Chart from 'chart.js/auto';

Alpine.plugin(collapse);
Alpine.plugin(focus);

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();

// AJAX cart: submit any .js-cart-form without a full page reload, then swap the
// drawer, update the badge, toast, and open the bag — preserving scroll position.
document.addEventListener('submit', async (e) => {
    const form = e.target.closest('.js-cart-form');
    if (!form) return;

    e.preventDefault();

    let data;
    try {
        const res = await fetch(form.action, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form),
        });
        data = await res.json();
    } catch (err) {
        form.submit(); // network failure or non-JSON response — fall back to a normal POST
        return;
    }

    try {
        const container = document.getElementById('cart-contents');
        if (container && typeof data.html === 'string') {
            container.innerHTML = data.html;
            window.Alpine?.initTree(container);
        }

        const badge = document.getElementById('cart-count');
        if (badge) {
            badge.textContent = data.count;
            badge.classList.toggle('hidden', !data.count);
        }

        if (data.message) {
            window.dispatchEvent(new CustomEvent('cart:toast', { detail: { type: data.type, msg: data.message } }));
        }
        if (data.open) window.dispatchEvent(new CustomEvent('cart:open'));
        window.dispatchEvent(new CustomEvent('cart-added'));
    } catch (err) {
        // DOM update failed after a successful request — don't re-POST (would double-add).
        console.error('cart UI update failed', err);
    }
});

function renderReportCharts() {
    const r = window.__report;
    if (!r) return;

    const revEl = document.getElementById('repRevenue');
    if (revEl) {
        new Chart(revEl, {
            type: 'line',
            data: {
                labels: r.sales.labels,
                datasets: [{
                    label: 'Revenue', data: r.sales.revenue,
                    borderColor: '#691d2a', backgroundColor: 'rgba(105,29,42,0.08)',
                    fill: true, tension: 0.35, pointRadius: 0, borderWidth: 2,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: (v) => r.symbol + v } }, x: { grid: { display: false } } },
            },
        });
    }

    const ordEl = document.getElementById('repOrders');
    if (ordEl) {
        new Chart(ordEl, {
            type: 'bar',
            data: {
                labels: r.sales.labels,
                datasets: [{ label: 'Orders', data: r.sales.orders, backgroundColor: '#D4A853', borderRadius: 4 }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } },
            },
        });
    }

    const payEl = document.getElementById('repPayment');
    if (payEl) {
        new Chart(payEl, {
            type: 'doughnut',
            data: {
                labels: r.payment.labels,
                datasets: [{ data: r.payment.values, backgroundColor: ['#691d2a', '#D4A853', '#C9B49A'], borderWidth: 0 }],
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '62%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
            },
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    renderReportCharts();

    const data = window.__dashboard;
    if (!data) return;

    const salesEl = document.getElementById('salesChart');
    if (salesEl) {
        new Chart(salesEl, {
            type: 'line',
            data: {
                labels: data.sales.labels,
                datasets: [{
                    label: 'Revenue',
                    data: data.sales.revenue,
                    borderColor: '#691d2a',
                    backgroundColor: 'rgba(105,29,42,0.08)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointBackgroundColor: '#691d2a',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: (v) => data.symbol + v } },
                    x: { grid: { display: false } },
                },
            },
        });
    }

    const pnlEl = document.getElementById('pnlChart');
    if (pnlEl && data.pnl) {
        new Chart(pnlEl, {
            type: 'bar',
            data: {
                labels: data.pnl.labels,
                datasets: [
                    { type: 'bar', label: 'Revenue', data: data.pnl.revenue, backgroundColor: '#D4A853', borderRadius: 4, order: 2 },
                    { type: 'bar', label: 'Expenses', data: data.pnl.expenses, backgroundColor: 'rgba(105,29,42,0.35)', borderRadius: 4, order: 2 },
                    {
                        type: 'line', label: 'Net profit', data: data.pnl.profit,
                        borderColor: '#16a34a', backgroundColor: '#16a34a',
                        tension: 0.35, pointRadius: 3, borderWidth: 2, order: 1,
                        segment: { borderColor: (ctx) => ctx.p1.parsed.y < 0 ? '#dc2626' : '#16a34a' },
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + data.symbol + Number(c.parsed.y).toLocaleString() } },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: (v) => data.symbol + v } },
                    x: { grid: { display: false } },
                },
            },
        });
    }

    const statusEl = document.getElementById('statusChart');
    if (statusEl) {
        const labels = Object.keys(data.status);
        const values = Object.values(data.status);
        new Chart(statusEl, {
            type: 'doughnut',
            data: {
                labels: labels.map((l) => l.charAt(0).toUpperCase() + l.slice(1)),
                datasets: [{
                    data: values,
                    backgroundColor: labels.map((l) => data.statusColors[l] || '#9ca3af'),
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
            },
        });
    }
});
