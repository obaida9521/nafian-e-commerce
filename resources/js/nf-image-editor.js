/**
 * Crop / rotate / resize images in the browser before they are uploaded.
 *
 * Any `<input type="file" data-image-editor>` is intercepted: when files are picked (or dropped
 * and re-dispatched as a `change`), each image opens in an editor modal, one after another.
 * The edited results replace the input's files and a fresh `change` event is dispatched, so the
 * page's own preview handlers (`@change="…"`) run exactly as before — just with edited files.
 *
 * Per-input options:
 *   data-aspect="0.909"   default crop ratio (width / height); omit for free crop
 *   data-max-width="1600" default longest output width in px
 *
 * SVG and GIF files (vector / animated) are passed through untouched.
 */

const ASPECTS = [
    { label: 'মুক্ত', value: null },
    { label: '১:১', value: 1 },
    { label: '৪:৫', value: 4 / 5 },
    { label: '১:১.১', value: 1 / 1.1 },
    { label: '৩:৪', value: 3 / 4 },
    { label: '৪:৩', value: 4 / 3 },
    { label: '১৬:৯', value: 16 / 9 },
];
const WIDTHS = [null, 2000, 1600, 1200, 800];
const MIN_CROP = 24;
const BN = '০১২৩৪৫৬৭৮৯';
const bn = (n) => String(n).replace(/[0-9]/g, (d) => BN[d]);

function loadImage(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => resolve({ img, url });
        img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('unreadable image')); };
        img.src = url;
    });
}

function canvasToBlob(canvas, type, quality) {
    return new Promise((resolve) => canvas.toBlob(resolve, type, quality));
}

class ImageEditor {
    /**
     * @returns {Promise<File|null|false>} edited file, the original (`false` = keep as is), or null to drop it
     */
    async edit(file, { aspect = null, maxWidth = 1600, index = 0, total = 1 } = {}) {
        const { img, url } = await loadImage(file);
        this.file = file;
        this.img = img;
        this.rotation = 0;
        this.aspect = aspect;
        this.maxWidth = maxWidth;
        this.buildSource();
        this.resetCrop();
        this.render(index, total);

        return new Promise((resolve) => {
            this.resolve = (result) => {
                URL.revokeObjectURL(url);
                this.teardown();
                resolve(result);
            };
        });
    }

    /** Rotated copy of the original at full resolution: everything crops from this. */
    buildSource() {
        const { img, rotation } = this;
        const swap = rotation % 180 !== 0;
        const canvas = document.createElement('canvas');
        canvas.width = swap ? img.naturalHeight : img.naturalWidth;
        canvas.height = swap ? img.naturalWidth : img.naturalHeight;
        const ctx = canvas.getContext('2d');
        ctx.translate(canvas.width / 2, canvas.height / 2);
        ctx.rotate((rotation * Math.PI) / 180);
        ctx.drawImage(img, -img.naturalWidth / 2, -img.naturalHeight / 2);
        this.source = canvas;
    }

    /** Largest centred crop with the current aspect ratio (or the whole image). */
    resetCrop() {
        const W = this.source.width;
        const H = this.source.height;
        if (!this.aspect) {
            this.crop = { x: 0, y: 0, w: W, h: H };
            return;
        }
        let w = W;
        let h = w / this.aspect;
        if (h > H) { h = H; w = h * this.aspect; }
        this.crop = { x: (W - w) / 2, y: (H - h) / 2, w, h };
    }

    render(index, total) {
        this.root = document.createElement('div');
        this.root.className = 'nf-imged';
        this.root.innerHTML = `
            <div class="nf-imged-panel" role="dialog" aria-modal="true" aria-label="ছবি সম্পাদনা">
                <div class="nf-imged-head">
                    <div>
                        <div class="nf-imged-title">ছবি সম্পাদনা${total > 1 ? ` <span>(${bn(index + 1)}/${bn(total)})</span>` : ''}</div>
                        <div class="nf-imged-file"></div>
                    </div>
                    <button type="button" class="nf-imged-x" data-act="cancel" aria-label="বাতিল">✕</button>
                </div>
                <div class="nf-imged-stage"><div class="nf-imged-canvas-wrap"><canvas></canvas>
                    <div class="nf-imged-crop">
                        <span class="nf-imged-grid"></span>
                        ${['nw', 'ne', 'sw', 'se'].map((c) => `<span class="nf-imged-handle is-${c}" data-handle="${c}"></span>`).join('')}
                    </div>
                </div></div>
                <div class="nf-imged-tools">
                    <div class="nf-imged-row">
                        <span class="nf-imged-label">অনুপাত</span>
                        <div class="nf-imged-chips" data-group="aspect">
                            ${ASPECTS.map((a, i) => `<button type="button" class="nf-imged-chip" data-aspect="${i}">${a.label}</button>`).join('')}
                        </div>
                    </div>
                    <div class="nf-imged-row">
                        <span class="nf-imged-label">সর্বোচ্চ চওড়া</span>
                        <div class="nf-imged-chips" data-group="width">
                            ${WIDTHS.map((w, i) => `<button type="button" class="nf-imged-chip" data-width="${i}">${w ? `${bn(w)} px` : 'মূল'}</button>`).join('')}
                        </div>
                    </div>
                    <div class="nf-imged-row nf-imged-meta">
                        <button type="button" class="nf-imged-chip" data-act="rotate">⟳ ঘোরান</button>
                        <button type="button" class="nf-imged-chip" data-act="reset">রিসেট</button>
                        <span class="nf-imged-out"></span>
                    </div>
                </div>
                <div class="nf-imged-actions">
                    <button type="button" class="nf-imged-btn is-ghost" data-act="original">মূল ছবি রাখুন</button>
                    <button type="button" class="nf-imged-btn is-primary" data-act="apply">প্রয়োগ করুন</button>
                </div>
            </div>`;
        this.root.querySelector('.nf-imged-file').textContent = this.file.name;
        document.body.append(this.root);
        document.documentElement.classList.add('nf-imged-lock');

        this.canvas = this.root.querySelector('canvas');
        this.wrap = this.root.querySelector('.nf-imged-canvas-wrap');
        this.stage = this.root.querySelector('.nf-imged-stage');
        this.cropEl = this.root.querySelector('.nf-imged-crop');

        this.root.addEventListener('click', (e) => this.onClick(e));
        this.cropEl.addEventListener('pointerdown', (e) => this.onPointerDown(e));
        this.onKey = (e) => {
            if (e.key === 'Escape') this.resolve(null);
            if (e.key === 'Enter' && !e.target.matches('button')) this.apply();
        };
        this.onResize = () => this.layout();
        document.addEventListener('keydown', this.onKey);
        window.addEventListener('resize', this.onResize);

        this.syncChips();
        requestAnimationFrame(() => {
            this.root.classList.add('is-open');
            this.layout();
        });
    }

    /** Fit the rotated source into the stage and draw a preview-sized copy. */
    layout() {
        if (!this.root) return;
        const maxW = this.stage.clientWidth - 24;
        const maxH = this.stage.clientHeight - 24;
        const { width: W, height: H } = this.source;
        this.scale = Math.min(maxW / W, maxH / H, 1);
        const w = Math.max(1, Math.round(W * this.scale));
        const h = Math.max(1, Math.round(H * this.scale));
        const dpr = window.devicePixelRatio || 1;
        this.canvas.width = Math.round(w * dpr);
        this.canvas.height = Math.round(h * dpr);
        this.canvas.style.width = `${w}px`;
        this.canvas.style.height = `${h}px`;
        this.wrap.style.width = `${w}px`;
        this.wrap.style.height = `${h}px`;
        const ctx = this.canvas.getContext('2d');
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(this.source, 0, 0, this.canvas.width, this.canvas.height);
        this.drawCrop();
    }

    drawCrop() {
        const { x, y, w, h } = this.crop;
        const s = this.scale;
        Object.assign(this.cropEl.style, { left: `${x * s}px`, top: `${y * s}px`, width: `${w * s}px`, height: `${h * s}px` });
        const out = this.outputSize();
        this.root.querySelector('.nf-imged-out').textContent = `আউটপুট: ${bn(out.w)} × ${bn(out.h)} px`;
    }

    outputSize() {
        const factor = this.maxWidth && this.crop.w > this.maxWidth ? this.maxWidth / this.crop.w : 1;
        return { w: Math.max(1, Math.round(this.crop.w * factor)), h: Math.max(1, Math.round(this.crop.h * factor)) };
    }

    syncChips() {
        this.root.querySelectorAll('[data-aspect]').forEach((el) => {
            el.classList.toggle('is-on', ASPECTS[+el.dataset.aspect].value === this.aspect
                || (this.aspect && ASPECTS[+el.dataset.aspect].value && Math.abs(ASPECTS[+el.dataset.aspect].value - this.aspect) < 0.001));
        });
        this.root.querySelectorAll('[data-width]').forEach((el) => el.classList.toggle('is-on', WIDTHS[+el.dataset.width] === this.maxWidth));
    }

    onClick(e) {
        const el = e.target.closest('button');
        if (!el) {
            if (e.target === this.root) this.resolve(null); // backdrop
            return;
        }
        if (el.dataset.aspect !== undefined) {
            this.aspect = ASPECTS[+el.dataset.aspect].value;
            this.resetCrop();
            this.syncChips();
            this.drawCrop();
        } else if (el.dataset.width !== undefined) {
            this.maxWidth = WIDTHS[+el.dataset.width];
            this.syncChips();
            this.drawCrop();
        } else if (el.dataset.act === 'rotate') {
            this.rotation = (this.rotation + 90) % 360;
            this.buildSource();
            this.resetCrop();
            this.layout();
        } else if (el.dataset.act === 'reset') {
            this.rotation = 0;
            this.buildSource();
            this.resetCrop();
            this.layout();
        } else if (el.dataset.act === 'original') {
            this.resolve(false);
        } else if (el.dataset.act === 'cancel') {
            this.resolve(null);
        } else if (el.dataset.act === 'apply') {
            this.apply();
        }
    }

    /** Drag the box to move it, or a corner handle to resize (ratio kept when one is set). */
    onPointerDown(e) {
        e.preventDefault();
        const handle = e.target.dataset.handle;
        const start = { px: e.clientX, py: e.clientY, ...this.crop };
        const W = this.source.width;
        const H = this.source.height;
        const s = this.scale;
        this.cropEl.setPointerCapture(e.pointerId);

        const move = (ev) => {
            const dx = (ev.clientX - start.px) / s;
            const dy = (ev.clientY - start.py) / s;

            if (!handle) {
                this.crop.x = Math.min(Math.max(0, start.x + dx), W - start.w);
                this.crop.y = Math.min(Math.max(0, start.y + dy), H - start.h);
                return this.drawCrop();
            }

            // Anchor = the corner opposite the dragged handle.
            const west = handle.includes('w');
            const north = handle.includes('n');
            const ax = west ? start.x + start.w : start.x;
            const ay = north ? start.y + start.h : start.y;
            let w = Math.max(MIN_CROP, west ? start.w - dx : start.w + dx);
            let h = Math.max(MIN_CROP, north ? start.h - dy : start.h + dy);
            const maxW = west ? ax : W - ax;
            const maxH = north ? ay : H - ay;

            if (this.aspect) {
                // Follow whichever axis moved more, then fit inside the image.
                if (Math.abs(dx) >= Math.abs(dy)) h = w / this.aspect; else w = h * this.aspect;
                if (w > maxW) { w = maxW; h = w / this.aspect; }
                if (h > maxH) { h = maxH; w = h * this.aspect; }
            } else {
                w = Math.min(w, maxW);
                h = Math.min(h, maxH);
            }

            this.crop = { x: west ? ax - w : ax, y: north ? ay - h : ay, w, h };
            this.drawCrop();
        };
        const up = () => {
            this.cropEl.removeEventListener('pointermove', move);
            this.cropEl.removeEventListener('pointerup', up);
            this.cropEl.removeEventListener('pointercancel', up);
        };
        this.cropEl.addEventListener('pointermove', move);
        this.cropEl.addEventListener('pointerup', up);
        this.cropEl.addEventListener('pointercancel', up);
    }

    async apply() {
        const { w, h } = this.outputSize();
        const out = document.createElement('canvas');
        out.width = w;
        out.height = h;
        const ctx = out.getContext('2d');
        ctx.imageSmoothingQuality = 'high';
        const { x, y, w: cw, h: ch } = this.crop;
        ctx.drawImage(this.source, x, y, cw, ch, 0, 0, w, h);

        // WebP keeps transparency (logos) and is small; fall back to the original type.
        let blob = await canvasToBlob(out, 'image/webp', 0.88);
        if (!blob || blob.type !== 'image/webp') {
            const type = this.file.type === 'image/png' ? 'image/png' : 'image/jpeg';
            blob = await canvasToBlob(out, type, 0.9);
        }
        const ext = blob.type.split('/')[1].replace('jpeg', 'jpg');
        const base = this.file.name.replace(/\.[^.]+$/, '') || 'image';
        this.resolve(new File([blob], `${base}.${ext}`, { type: blob.type, lastModified: Date.now() }));
    }

    teardown() {
        document.removeEventListener('keydown', this.onKey);
        window.removeEventListener('resize', this.onResize);
        document.documentElement.classList.remove('nf-imged-lock');
        this.root?.remove();
        this.root = null;
    }
}

const SKIP = /image\/(svg\+xml|gif)/;

async function editFiles(files, input) {
    const aspect = parseFloat(input.dataset.aspect) || null;
    const maxWidth = input.dataset.maxWidth ? parseInt(input.dataset.maxWidth, 10) : 1600;
    const results = [];
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (!file.type.startsWith('image/') || SKIP.test(file.type)) {
            results.push(file);
            continue;
        }
        try {
            const edited = await new ImageEditor().edit(file, { aspect, maxWidth, index: i, total: files.length });
            if (edited === false) results.push(file);
            else if (edited) results.push(edited);
        } catch {
            results.push(file); // unreadable in the browser: let the server validate it
        }
    }
    return results;
}

export function initImageEditor() {
    document.addEventListener('change', async (e) => {
        const input = e.target;
        if (!(input instanceof HTMLInputElement) || input.type !== 'file' || !input.hasAttribute('data-image-editor')) return;
        if (input._nfEdited) { input._nfEdited = false; return; } // our own re-dispatch
        const files = [...(input.files || [])];
        if (!files.length) return;

        // Hold the page's handlers until the images are edited.
        e.stopImmediatePropagation();
        const results = await editFiles(files, input);
        const dt = new DataTransfer();
        results.forEach((f) => dt.items.add(f));
        input.files = dt.files;
        if (!results.length) { input.value = ''; return; }
        input._nfEdited = true;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }, true);
}
