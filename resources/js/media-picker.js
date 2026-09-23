/**
 * Admin media picker (Alpine store `mediaPicker`, markup in components/admin/media-picker).
 *
 *   const items = await window.openMediaPicker({ multiple: true, title: 'পণ্যের ছবি' });
 *   // → [{ key: 'media:12', url, thumb, name, ... }]  (empty array when cancelled)
 *
 * Items come from the media manager's JSON feed; uploads inside the picker go to the library
 * (through the crop editor) and are pre-selected.
 */
document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.store('mediaPicker', {
        open: false,
        multiple: false,
        title: '',
        items: [],
        selected: [],
        source: '',
        search: '',
        page: 1,
        lastPage: 1,
        loading: false,
        uploading: false,
        error: '',
        resolve: null,

        el() {
            return document.getElementById('media-picker');
        },

        show({ multiple = false, title = 'মিডিয়া থেকে বেছে নিন' } = {}) {
            this.multiple = multiple;
            this.title = title;
            this.selected = [];
            this.search = '';
            this.source = '';
            this.error = '';
            this.open = true;
            this.load(true);

            return new Promise((resolve) => { this.resolve = resolve; });
        },

        async load(reset = false) {
            if (this.loading) return;
            this.loading = true;
            this.error = '';
            const page = reset ? 1 : this.page + 1;
            const params = new URLSearchParams({ page });
            if (this.source) params.set('source', this.source);
            if (this.search.trim()) params.set('search', this.search.trim());

            try {
                const res = await fetch(`${this.el().dataset.indexUrl}?${params}`, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error(res.status);
                const data = await res.json();
                this.items = reset ? data.data : [...this.items, ...data.data];
                this.page = data.current_page;
                this.lastPage = data.last_page;
            } catch {
                this.error = 'ছবিগুলো আনা যায়নি। আবার চেষ্টা করুন।';
            } finally {
                this.loading = false;
            }
        },

        filter(source) {
            this.source = source;
            this.load(true);
        },

        isSelected(item) {
            return this.selected.some((s) => s.key === item.key);
        },

        toggle(item) {
            if (this.isSelected(item)) {
                this.selected = this.selected.filter((s) => s.key !== item.key);
            } else if (this.multiple) {
                this.selected.push(item);
            } else {
                this.selected = [item];
            }
        },

        async upload(input) {
            const files = [...(input.files || [])];
            if (!files.length) return;
            this.uploading = true;
            this.error = '';
            const body = new FormData();
            files.forEach((f) => body.append('files[]', f));

            try {
                const res = await fetch(this.el().dataset.storeUrl, {
                    method: 'POST',
                    body,
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'upload failed');
                this.source = '';
                this.items = [...data.data, ...this.items.filter((i) => !data.data.some((d) => d.key === i.key))];
                data.data.forEach((item) => { if (!this.isSelected(item)) (this.multiple || !this.selected.length ? this.selected.push(item) : (this.selected = [item])); });
                window.adminToast?.('success', data.message);
            } catch (e) {
                this.error = e.message && e.message !== 'upload failed' ? e.message : 'আপলোড করা যায়নি।';
            } finally {
                this.uploading = false;
                input.value = '';
            }
        },

        confirm() {
            this.finish(this.selected);
        },

        cancel() {
            this.finish([]);
        },

        finish(result) {
            this.open = false;
            this.resolve?.(result);
            this.resolve = null;
        },
    });

    window.openMediaPicker = (options) => Alpine.store('mediaPicker').show(options);
});
