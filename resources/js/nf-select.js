/**
 * Custom dropdowns for every <select> on the site.
 *
 * The native <select> stays in the DOM (hidden) as the source of truth, so form submits,
 * Alpine `x-model`, `@change` and inline `onchange` handlers keep working unchanged. A styled
 * trigger button takes its place; picking an option sets `select.value` and dispatches the
 * usual `input` + `change` events. Programmatic changes (x-model, form reset, x-for options)
 * are mirrored back onto the trigger.
 *
 * Desktop: floating menu under the trigger (flips up near the viewport bottom).
 * Phones (< 640px): bottom sheet with a title, matching the app-style layout.
 *
 * Opt out with `<select data-native>`; `multiple` / `size > 1` selects are left alone.
 */

const PHONE = window.matchMedia('(max-width: 639px)');
const CHEVRON = '<svg class="nf-select-chevron" width="12" height="8" viewBox="0 0 12 8" aria-hidden="true"><path d="M1 1l5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
const CHECK = '<svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';

let uid = 0;
let openInstance = null;

class NfSelect {
    constructor(select) {
        this.select = select;
        this.id = `nf-select-${++uid}`;
        this.highlight = -1;

        this.trigger = document.createElement('button');
        this.trigger.type = 'button';
        this.trigger.setAttribute('aria-haspopup', 'listbox');
        this.trigger.setAttribute('aria-expanded', 'false');
        this.trigger.innerHTML = `<span class="nf-select-label"></span>${CHEVRON}`;
        this.labelEl = this.trigger.firstElementChild;
        this.syncTriggerAttributes();

        select.classList.add('nf-select-native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');
        select.after(this.trigger);
        select._nfSelect = this;

        this.trigger.addEventListener('click', () => (this.isOpen ? this.close() : this.open()));
        this.trigger.addEventListener('keydown', (e) => this.onKeydown(e));
        // A <label for> / wrapping <label> activates the hidden select: forward that to us.
        select.addEventListener('focus', () => this.trigger.focus());
        select.addEventListener('click', (e) => { e.preventDefault(); this.open(); });
        select.addEventListener('change', () => this.refresh());
        select.form?.addEventListener('reset', () => setTimeout(() => this.refresh()));

        this.observer = new MutationObserver(() => { this.syncTriggerAttributes(); this.refresh(); if (this.isOpen) this.render(); });
        this.observer.observe(select, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ['class', 'style', 'disabled', 'selected', 'label'] });

        this.refresh();
    }

    get isOpen() {
        return openInstance === this;
    }

    syncTriggerAttributes() {
        const classes = [...this.select.classList].filter((c) => c !== 'nf-select-native');
        this.trigger.className = ['nf-select-trigger', ...classes].join(' ');
        const style = this.select.getAttribute('style');
        style ? this.trigger.setAttribute('style', style) : this.trigger.removeAttribute('style');
        this.trigger.disabled = this.select.disabled;
        const label = this.select.getAttribute('aria-label');
        if (label) this.trigger.setAttribute('aria-label', label);
    }

    options() {
        return [...this.select.options];
    }

    refresh() {
        const option = this.select.options[this.select.selectedIndex];
        const text = option ? option.label || option.text : '';
        this.labelEl.textContent = text.trim() || ' ';
        this.trigger.classList.toggle('is-placeholder', !option || option.value === '');
    }

    /** Title for the phone sheet: aria-label, or the <label> pointing at / wrapping the select. */
    title() {
        const s = this.select;
        if (s.getAttribute('aria-label')) return s.getAttribute('aria-label');
        const label = (s.id && document.querySelector(`label[for="${CSS.escape(s.id)}"]`)) || s.closest('label');
        const text = label ? [...label.childNodes].filter((n) => n !== s && n !== this.trigger).map((n) => n.textContent).join(' ') : '';
        return text.replace(/\s+/g, ' ').replace(/[:：]\s*$/, '').trim() || 'বেছে নিন';
    }

    open() {
        if (this.select.disabled || this.isOpen) return;
        openInstance?.close(false);
        openInstance = this;
        this.phone = PHONE.matches;
        this.highlight = this.select.selectedIndex;

        this.backdrop = null;
        if (this.phone) {
            this.backdrop = document.createElement('div');
            this.backdrop.className = 'nf-select-backdrop';
            this.backdrop.addEventListener('click', () => this.close());
            document.body.append(this.backdrop);
        }

        this.menu = document.createElement('div');
        this.menu.className = this.phone ? 'nf-select-menu is-sheet' : 'nf-select-menu';
        this.menu.id = this.id;
        this.menu.setAttribute('role', 'listbox');
        this.menu.addEventListener('mousedown', (e) => e.preventDefault()); // keep focus on the trigger
        document.body.append(this.menu);
        this.trigger.setAttribute('aria-expanded', 'true');
        this.trigger.setAttribute('aria-controls', this.id);
        this.render();
        this.position();

        this.onOutside = (e) => { if (!this.menu.contains(e.target) && !this.trigger.contains(e.target)) this.close(false); };
        this.onReflow = () => (this.phone ? null : this.position());
        document.addEventListener('pointerdown', this.onOutside, true);
        window.addEventListener('resize', this.onReflow);
        window.addEventListener('scroll', this.onReflow, true);
        requestAnimationFrame(() => this.menu?.classList.add('is-open'));
    }

    close(refocus = true) {
        if (!this.isOpen) return;
        openInstance = null;
        document.removeEventListener('pointerdown', this.onOutside, true);
        window.removeEventListener('resize', this.onReflow);
        window.removeEventListener('scroll', this.onReflow, true);
        this.menu.remove();
        this.backdrop?.remove();
        this.menu = this.backdrop = null;
        this.trigger.setAttribute('aria-expanded', 'false');
        if (refocus) this.trigger.focus({ preventScroll: true });
    }

    render() {
        const options = this.options();
        const selectedIndex = this.select.selectedIndex;
        const parts = [];

        if (this.phone) {
            parts.push(`<div class="nf-select-grip"></div><div class="nf-select-title"></div>`);
        }

        options.forEach((option, index) => {
            const group = option.parentElement.tagName === 'OPTGROUP' ? option.parentElement : null;
            if (group && group.firstElementChild === option) {
                parts.push(`<div class="nf-select-group"></div>`);
            }
            if (option.hidden) return;
            const classes = ['nf-select-option'];
            if (index === selectedIndex) classes.push('is-selected');
            if (index === this.highlight) classes.push('is-active');
            if (option.disabled || group?.disabled) classes.push('is-disabled');
            if (option.value === '') classes.push('is-placeholder');
            parts.push(`<div class="${classes.join(' ')}" role="option" data-index="${index}" aria-selected="${index === selectedIndex}"${option.disabled ? ' aria-disabled="true"' : ''}><span></span>${CHECK}</div>`);
        });

        this.menu.innerHTML = parts.join('');
        // Text via textContent so option labels are never parsed as HTML.
        if (this.phone) this.menu.querySelector('.nf-select-title').textContent = this.title();
        this.menu.querySelectorAll('.nf-select-group').forEach((el, i) => {
            el.textContent = this.select.querySelectorAll('optgroup')[i]?.label ?? '';
        });
        this.menu.querySelectorAll('.nf-select-option').forEach((el) => {
            const option = options[+el.dataset.index];
            el.firstElementChild.textContent = option.label || option.text;
            el.addEventListener('click', () => this.choose(+el.dataset.index));
            el.addEventListener('mousemove', () => this.setHighlight(+el.dataset.index, false));
        });

        this.menu.querySelector('.is-selected')?.scrollIntoView({ block: 'nearest' });
    }

    position() {
        if (!this.menu || this.phone) return;
        const rect = this.trigger.getBoundingClientRect();
        const gap = 6;
        const width = Math.max(rect.width, 180);
        const below = window.innerHeight - rect.bottom - gap - 12;
        const above = rect.top - gap - 12;
        const wanted = Math.min(this.menu.scrollHeight, 300);
        const flip = below < Math.min(wanted, 200) && above > below;
        const maxHeight = Math.max(120, Math.min(300, flip ? above : below));

        Object.assign(this.menu.style, {
            left: `${Math.min(Math.max(8, rect.left), window.innerWidth - width - 8)}px`,
            width: `${width}px`,
            maxHeight: `${maxHeight}px`,
            top: flip ? '' : `${rect.bottom + gap}px`,
            bottom: flip ? `${window.innerHeight - rect.top + gap}px` : '',
        });
        this.menu.classList.toggle('is-up', flip);
    }

    choose(index) {
        const option = this.select.options[index];
        if (!option || option.disabled) return;
        const changed = this.select.selectedIndex !== index;
        this.select.selectedIndex = index;
        this.close();
        if (changed) {
            this.select.dispatchEvent(new Event('input', { bubbles: true }));
            this.select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    setHighlight(index, scroll = true) {
        this.highlight = index;
        this.menu?.querySelectorAll('.nf-select-option').forEach((el) => el.classList.toggle('is-active', +el.dataset.index === index));
        if (scroll) this.menu?.querySelector(`[data-index="${index}"]`)?.scrollIntoView({ block: 'nearest' });
    }

    moveHighlight(step) {
        const options = this.options();
        let i = this.highlight;
        for (let n = 0; n < options.length; n++) {
            i = (i + step + options.length) % options.length;
            if (!options[i].disabled && !options[i].hidden) return this.setHighlight(i);
        }
    }

    onKeydown(e) {
        const key = e.key;
        if (!this.isOpen) {
            if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(key)) {
                e.preventDefault();
                this.open();
            }
            return;
        }
        if (key === 'Escape') { e.preventDefault(); this.close(); }
        else if (key === 'Tab') { this.close(false); }
        else if (key === 'ArrowDown') { e.preventDefault(); this.moveHighlight(1); }
        else if (key === 'ArrowUp') { e.preventDefault(); this.moveHighlight(-1); }
        else if (key === 'Home') { e.preventDefault(); this.highlight = -1; this.moveHighlight(1); }
        else if (key === 'End') { e.preventDefault(); this.highlight = this.options().length; this.moveHighlight(-1); }
        else if (key === 'Enter' || key === ' ') { e.preventDefault(); if (this.highlight >= 0) this.choose(this.highlight); }
    }

    destroy() {
        if (this.isOpen) this.close(false);
        this.observer.disconnect();
        this.trigger.remove();
    }
}

function enhance(root) {
    const selects = root.matches?.('select') ? [root] : root.querySelectorAll?.('select') ?? [];
    for (const select of selects) {
        if (select._nfSelect || select.multiple || select.size > 1 || select.hasAttribute('data-native')) continue;
        // Skip selects still inside an Alpine <template> (x-for / x-if clones get enhanced once inserted).
        if (select.closest('template')) continue;
        new NfSelect(select);
    }
}

function cleanup(root) {
    const selects = root.matches?.('select') ? [root] : root.querySelectorAll?.('select') ?? [];
    for (const select of selects) {
        if (select._nfSelect && !select.isConnected) {
            select._nfSelect.destroy();
            delete select._nfSelect;
        }
    }
}

/**
 * x-model and scripts change the value without events (`select.value`, `selectedIndex`,
 * or Alpine's `option.selected = …`). Patch those setters once so the trigger follows.
 */
function watchProgrammaticChanges() {
    const pending = new Set();
    const queue = (select) => {
        if (!select?._nfSelect) return;
        if (pending.size === 0) queueMicrotask(() => { pending.forEach((s) => s._nfSelect?.refresh()); pending.clear(); });
        pending.add(select);
    };
    const wrap = (proto, prop, selectOf) => {
        const descriptor = Object.getOwnPropertyDescriptor(proto, prop);
        Object.defineProperty(proto, prop, {
            configurable: true,
            enumerable: descriptor.enumerable,
            get: descriptor.get,
            set(value) { descriptor.set.call(this, value); queue(selectOf(this)); },
        });
    };
    wrap(HTMLSelectElement.prototype, 'value', (el) => el);
    wrap(HTMLSelectElement.prototype, 'selectedIndex', (el) => el);
    wrap(HTMLOptionElement.prototype, 'selected', (el) => el.closest('select'));
}

export function initNfSelect() {
    watchProgrammaticChanges();
    enhance(document);
    new MutationObserver((mutations) => {
        for (const m of mutations) {
            m.addedNodes.forEach((node) => node.nodeType === 1 && enhance(node));
            m.removedNodes.forEach((node) => node.nodeType === 1 && cleanup(node));
        }
    }).observe(document.body, { childList: true, subtree: true });
    PHONE.addEventListener('change', () => openInstance?.close(false));
}
