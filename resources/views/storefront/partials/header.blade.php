@inject('cart', 'App\Services\CartService')
<header class="sticky top-0 z-40 border-b border-[#EADBC4]" style="background:rgba(255,242,227,0.88);backdrop-filter:blur(14px);">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 h-[62px] flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <button @click="menuOpen=true" class="md:hidden text-[#691d2a]" aria-label="Menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>
            <a href="{{ route('store.home') }}" class="flex items-center gap-2.5">
                @if($logo = brand_logo())
                    <span class="w-10 h-10 rounded-xl bg-white border border-[#EADBC4] grid place-items-center overflow-hidden p-1 flex-none">
                        <img src="{{ $logo }}" alt="{{ config('shop.name') }}" class="max-w-full max-h-full object-contain">
                    </span>
                @endif
                <span class="font-bold text-xl tracking-[0.24em] text-[#691d2a]">NAFIAN</span>
            </a>
        </div>
        @php
            $activeCat = optional(request()->route('category'))->slug;
            $navItems = [
                ['label' => 'Home', 'url' => route('store.home'), 'active' => request()->routeIs('store.home')],
                ['label' => 'New In', 'url' => route('store.shop'), 'active' => request()->routeIs('store.shop')],
                ['label' => 'Bags', 'url' => route('store.shop.category', 'bags'), 'active' => $activeCat === 'bags'],
                ['label' => 'Apparel', 'url' => route('store.shop.category', 'apparel'), 'active' => $activeCat === 'apparel'],
                ['label' => 'Accessories', 'url' => route('store.shop.category', 'accessories'), 'active' => $activeCat === 'accessories'],
            ];
        @endphp
        <nav class="hidden md:flex gap-[30px] text-sm font-medium">
            @foreach($navItems as $item)
                <a href="{{ $item['url'] }}" @class([
                    'relative pb-0.5 transition-colors',
                    'text-[#691d2a] font-semibold' => $item['active'],
                    'text-gray-700 hover:text-[#691d2a]' => ! $item['active'],
                ])>
                    {{ $item['label'] }}
                    @if($item['active'])
                        <span class="absolute -bottom-1 left-0 right-0 h-0.5 rounded-full bg-[#691d2a]"></span>
                    @endif
                </a>
            @endforeach
            <a href="{{ route('store.shop') }}" class="text-[#B45309] hover:text-[#92400E]">Sale</a>
        </nav>
        <div class="flex items-center gap-4 text-[#691d2a]">
            <a href="{{ route('store.shop') }}" aria-label="Search">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            </a>
            <a href="{{ auth('web')->check() ? route('store.account.orders') : route('login') }}" aria-label="Account">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6"/></svg>
            </a>
            <button @click="cartOpen=true" class="relative flex" aria-label="Cart">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M6 7h12l-1 13H7L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
                <span id="cart-count" class="absolute -top-[7px] -right-2 bg-[#691d2a] text-white text-[10px] font-semibold min-w-[16px] h-4 rounded-[9px] flex items-center justify-center px-1 {{ $cart->getCount() > 0 ? '' : 'hidden' }}">{{ $cart->getCount() }}</span>
            </button>
        </div>
    </div>
</header>
