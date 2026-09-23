@inject('cart', 'App\Services\CartService')
@php
    $activeCategoryId = optional(request()->route('category'))->id;
    $cartCount = $cart->getCount();
    $showSearch = ! View::hasSection('hide_header_search');
@endphp

{{-- Mobile header (app-style; pages may replace it) --}}
@hasSection('mobile_header')
    @yield('mobile_header')
@else
    <div class="sm:hidden sticky top-0 z-40 bg-white flex items-center justify-between px-5 pt-3 pb-3">
        <a href="{{ route('store.home') }}" class="flex items-center gap-2 min-w-0">
            @if($logo = brand_logo())
                <img src="{{ $logo }}" alt="" onerror="this.remove()" class="h-7 w-auto max-w-[44px] object-contain flex-none">
            @endif
            <span class="font-display text-[19px] tracking-[0.3em] pl-[0.3em] text-espresso">NAFIAN</span>
        </a>
        <div class="flex gap-2">
            <button type="button" @click="searchOpen = true" class="bg-sand rounded-full w-9 h-9 grid place-items-center text-espresso" aria-label="খুঁজুন"><x-ui.icon name="search" :size="18" :stroke="2" /></button>
            <a href="{{ route('store.cart') }}" class="relative bg-espresso text-white rounded-full w-9 h-9 grid place-items-center" aria-label="ব্যাগ">
                <x-ui.icon name="bag" :size="18" :stroke="2" />
                <span @class(['js-cart-count absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-accent text-white text-[10.5px] leading-[18px] text-center font-semibold ring-2 ring-white', 'hidden' => $cartCount < 1]) data-bn data-hide-empty>{{ bn_digits($cartCount) }}</span>
            </a>
        </div>
    </div>
@endif

{{-- Desktop / tablet --}}
<header class="hidden sm:block sticky top-0 z-40 bg-white/95 backdrop-blur">
    <div class="flex items-center justify-between gap-6 px-8 py-[18px] max-[1100px]:flex-col max-[1100px]:gap-3.5">
        <nav class="flex flex-wrap gap-[26px] text-[15px] font-medium text-espresso max-[1100px]:order-2 max-[1100px]:justify-center max-[1100px]:w-full">
            @foreach(nav_categories() as $navCategory)
                <a href="{{ route('store.shop.category', $navCategory->slug) }}" @class(['hover:text-accent', 'text-accent' => $activeCategoryId === $navCategory->id])>{{ $navCategory->name }}</a>
            @endforeach
            <a href="{{ route('store.offers') }}" @class(['hover:text-accent', 'text-accent' => request()->routeIs('store.offers')])>অফার</a>
        </nav>

        <a href="{{ route('store.home') }}" class="flex items-center gap-3 max-[1100px]:order-1">
            @if($logo = brand_logo())
                <img src="{{ $logo }}" alt="{{ config('shop.name') }}" onerror="this.remove()" class="h-9 w-auto object-contain">
            @endif
            <span class="font-display text-[26px] tracking-[0.34em] pl-[0.34em] text-espresso">NAFIAN</span>
        </a>

        <div class="flex gap-3 items-center justify-end max-[1100px]:order-3 max-[1100px]:w-full">
            @if($showSearch)
                <form action="{{ route('store.shop') }}" method="GET" role="search" class="relative max-[1100px]:flex-1"
                      x-data="nfSearch(@js(route('store.search.suggest')), @js(route('store.shop')))"
                      @click.outside="open = false" @submit.prevent="submit()">
                    <input type="search" name="q" x-model="q" @input="fetchResults(); open = true" @focus="open = true"
                           value="{{ request('q') }}" x-init="q = $el.value" placeholder="সার্চ করুন…" aria-label="সার্চ করুন" autocomplete="off"
                           class="w-full min-w-[170px] bg-sand rounded-full pl-11 pr-5 py-[11px] text-[14px] text-espresso placeholder-muted outline-none focus:shadow-[inset_0_0_0_2px_#3B2F2D]">
                    <x-ui.icon name="search" :size="17" :stroke="2" class="pointer-events-none absolute left-[18px] top-1/2 -translate-y-1/2 text-muted" />
                    <div x-show="open && (products.length || recent.length || q.trim())" x-cloak x-transition.opacity
                         class="absolute right-0 top-[calc(100%+10px)] w-[360px] max-w-[90vw] bg-white rounded-[20px] p-5 z-50 shadow-[0_24px_60px_-30px_rgba(36,28,26,.55)]">
                        @include('storefront.partials.search-results')
                    </div>
                </form>
            @endif
            <a href="{{ route('store.track') }}" @class(['inline-flex items-center gap-2 rounded-full px-[18px] py-[11px] text-[13.5px] font-medium whitespace-nowrap', 'bg-espresso text-white' => request()->routeIs('store.track*'), 'bg-sand text-espresso hover:bg-sand-2' => ! request()->routeIs('store.track*')])><x-ui.icon name="box" :size="16" />অর্ডার ট্র্যাক</a>
            <a href="{{ route('store.cart') }}" @click.prevent="cartOpen = true" class="inline-flex items-center gap-2 bg-espresso text-white rounded-full px-5 py-[11px] text-[14px] font-medium whitespace-nowrap hover:bg-ink">
                <x-ui.icon name="bag" :size="17" /><span>ব্যাগ · <span id="cart-count" class="js-cart-count" data-bn>{{ bn_digits($cartCount) }}</span></span>
            </a>
        </div>
    </div>
</header>
