{{--
    Home "দেখতে থাকুন" grid with an "আরও দেখুন" button that appends the next page from store.discover.
    The seed keeps the random order stable across pages, so nothing repeats.
    @var array{products: \Illuminate\Support\Collection, has_more: bool} $discover
    @var int $seed
    @var string $gridClass
--}}
<div x-data="{
        offset: {{ $discover['products']->count() }},
        hasMore: @js($discover['has_more']),
        loading: false,
        async loadMore() {
            if (this.loading) return;
            this.loading = true;
            try {
                const params = new URLSearchParams({ seed: @js($seed), offset: this.offset });
                const response = await fetch(@js(route('store.discover')) + '?' + params, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (! response.ok) throw new Error(response.status);
                const data = await response.json();
                this.$refs.grid.insertAdjacentHTML('beforeend', data.html);
                this.offset += data.count;
                this.hasMore = data.has_more && data.count > 0;
            } catch (e) {
                $dispatch('cart:toast', { type: 'error', msg: 'লোড করা যায়নি, আবার চেষ্টা করুন।' });
            } finally {
                this.loading = false;
            }
        },
     }">
    <div x-ref="grid" class="{{ $gridClass }}">
        @include('storefront.partials.discover-items', ['products' => $discover['products']])
    </div>
    <div x-show="hasMore" x-transition.opacity class="mt-6 sm:mt-9 flex justify-center">
        <button type="button" @click="loadMore()" :disabled="loading"
                class="inline-flex items-center gap-2.5 rounded-full border border-line bg-white px-7 py-3 sm:px-9 sm:py-3.5 text-[14px] sm:text-[15px] font-semibold text-espresso transition hover:border-espresso hover:bg-espresso hover:text-white disabled:opacity-70 disabled:hover:bg-white disabled:hover:text-espresso">
            <span x-show="loading" x-cloak class="nf-spinner !w-4 !h-4 !border-2"></span>
            <span x-text="loading ? 'লোড হচ্ছে…' : 'আরও দেখুন'">আরও দেখুন</span>
        </button>
    </div>
</div>
