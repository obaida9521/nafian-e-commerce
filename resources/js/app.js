import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import Chart from 'chart.js/auto';
import { initNfSelect } from './nf-select';
import { initImageEditor } from './nf-image-editor';
import './media-picker';

Alpine.plugin(collapse);
Alpine.plugin(focus);

window.Alpine = Alpine;
window.Chart = Chart;

const BN_DIGITS = '০১২৩৪৫৬৭৮৯';

/** 1450 → "১,৪৫০" */
window.bnNumber = (value) =>
    Math.round(Number(value) || 0)
        .toLocaleString('en-US')
        .replace(/[0-9]/g, (d) => BN_DIGITS[d]);

/** "১৭" → "17" */
window.latinDigits = (value) => String(value ?? '').replace(/[০-৯]/g, (d) => String(BN_DIGITS.indexOf(d)));

const RECENT_KEY = 'nf-recent-searches';

const readRecent = () => {
    try {
        return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]').slice(0, 6);
    } catch {
        return [];
    }
};

// Search overlay / dropdown: live suggestions plus recent searches kept in this browser.
Alpine.data('nfSearch', (suggestUrl, shopUrl) => ({
    q: '',
    open: false,
    loading: false,
    suggestions: [],
    products: [],
    recent: readRecent(),
    timer: null,

    fetchResults() {
        clearTimeout(this.timer);
        const term = this.q.trim();
        if (!term) {
            this.suggestions = [];
            this.products = [];
            return;
        }
        this.timer = setTimeout(async () => {
            this.loading = true;
            try {
                const res = await fetch(`${suggestUrl}?q=${encodeURIComponent(term)}`, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                this.suggestions = data.suggestions ?? [];
                this.products = data.products ?? [];
            } catch {
                this.products = [];
            } finally {
                this.loading = false;
            }
        }, 180);
    },

    highlight(text) {
        const term = this.q.trim();
        const safe = String(text).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);
        if (!term) return safe;
        const i = safe.toLowerCase().indexOf(term.toLowerCase());
        if (i < 0) return safe;
        return `${safe.slice(0, i)}<b class="font-semibold">${safe.slice(i, i + term.length)}</b>${safe.slice(i + term.length)}`;
    },

    remember(term) {
        term = String(term).trim();
        if (!term) return;
        this.recent = [term, ...this.recent.filter((t) => t !== term)].slice(0, 6);
        try {
            localStorage.setItem(RECENT_KEY, JSON.stringify(this.recent));
        } catch {
            // Private mode — recent searches simply aren't kept.
        }
    },

    go(term) {
        this.remember(term);
        window.location = `${shopUrl}?q=${encodeURIComponent(term)}`;
    },

    submit() {
        if (this.q.trim()) this.go(this.q);
    },
}));

Alpine.start();

// Styled dropdowns for every <select> (see nf-select.js).
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNfSelect);
} else {
    initNfSelect();
}

// Crop / resize before upload for <input type=file data-image-editor> (see nf-image-editor.js).
initImageEditor();

// AJAX cart: submit any .js-cart-form without a full page reload, then swap the
// drawer (and the bag page when open), update the badges, toast, and open the bag.
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

    if (data.redirect) {
        window.location = data.redirect;
        return;
    }

    window.nfFlush?.(data.analytics);

    try {
        const container = document.getElementById('cart-contents');
        if (container && typeof data.html === 'string') {
            container.innerHTML = data.html;
            window.Alpine?.initTree(container);
        }

        const page = document.getElementById('bag-page');
        if (page && typeof data.page_html === 'string') {
            page.innerHTML = data.page_html;
            window.Alpine?.initTree(page);
        }

        document.querySelectorAll('.js-cart-count').forEach((badge) => {
            const count = String(data.count ?? 0);
            badge.textContent = badge.hasAttribute('data-bn') ? count.replace(/[0-9]/g, (d) => BN_DIGITS[d]) : count;
            if (badge.hasAttribute('data-hide-empty')) badge.classList.toggle('hidden', count === '0');
        });

        if (data.message) {
            window.dispatchEvent(new CustomEvent('cart:toast', { detail: { type: data.type, msg: data.message } }));
        }
        if (data.open && !page) window.dispatchEvent(new CustomEvent('cart:open'));
        window.dispatchEvent(new CustomEvent('cart-added'));
    } catch (err) {
        // DOM update failed after a successful request — don't re-POST (would double-add).
        console.error('cart UI update failed', err);
    }
});
