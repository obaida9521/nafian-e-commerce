{{-- Mobile search screen (Nafian Mobile · সার্চ). --}}
<div x-show="searchOpen" x-cloak x-transition.opacity class="sm:hidden fixed inset-0 z-[80] bg-white flex flex-col"
     x-data="nfSearch(@js(route('store.search.suggest')), @js(route('store.shop')))"
     x-effect="if (searchOpen) $nextTick(() => $refs.searchInput.focus())">
    <form @submit.prevent="submit()" class="flex-none px-5 pt-4 pb-3 flex gap-2.5 items-center">
        <input x-ref="searchInput" type="search" x-model="q" @input="fetchResults()" placeholder="সার্চ করুন…" autocomplete="off" enterkeyhint="search"
               class="flex-1 bg-sand-3 rounded-full px-[18px] py-3 text-[14.5px] text-ink placeholder-muted outline-none">
        <button type="button" @click="searchOpen = false; q = ''" class="text-[14px] font-medium text-accent">বাতিল</button>
    </form>
    <div class="flex-1 overflow-y-auto px-5 pt-1 pb-8">
        @include('storefront.partials.search-results')
    </div>
</div>
