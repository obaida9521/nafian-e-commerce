@inject('cart', 'App\Services\CartService')
@php
    $tabs = [
        ['label' => 'হোম', 'icon' => 'home', 'url' => route('store.home'), 'on' => request()->routeIs('store.home', 'store.order.confirmation', 'store.track*')],
        ['label' => 'শপ', 'icon' => 'shop', 'url' => route('store.shop'), 'on' => request()->routeIs('store.shop*', 'store.product')],
        ['label' => 'অফার', 'icon' => 'offer', 'url' => route('store.offers'), 'on' => request()->routeIs('store.offers')],
        ['label' => 'ব্যাগ', 'icon' => 'bag', 'url' => route('store.cart'), 'on' => request()->routeIs('store.cart', 'store.checkout')],
    ];
@endphp
{{-- Mobile bottom tab bar (Nafian Mobile). --}}
<nav class="sm:hidden fixed inset-x-0 bottom-0 z-50 bg-white grid grid-cols-4 h-[var(--nf-tabbar-h)] px-2 pt-2 pb-[env(safe-area-inset-bottom)] shadow-[0_-8px_22px_-20px_rgba(36,28,26,.8)]" aria-label="প্রধান">
    @foreach($tabs as $tab)
        <a href="{{ $tab['url'] }}" @class(['flex flex-col items-center justify-center gap-1 text-[11.5px]', 'text-espresso font-semibold' => $tab['on'], 'text-muted font-medium' => ! $tab['on']])
           @if($tab['on']) aria-current="page" @endif>
            <span class="relative">
                <x-ui.icon :name="$tab['icon']" :size="23" :stroke="$tab['on'] ? 2.1 : 1.7" />
                @if($loop->last)
                    <span @class(['js-cart-count absolute -top-1.5 -right-2.5 min-w-4 h-4 px-1 rounded-full bg-accent text-white text-[10px] leading-4 text-center font-semibold', 'hidden' => $cart->getCount() < 1]) data-bn data-hide-empty>{{ bn_digits($cart->getCount()) }}</span>
                @endif
            </span>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
