<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Considered pieces for a quieter wardrobe') — {{ config('shop.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'DM Sans', system-ui, sans-serif; background: #fff2e3; color: #111; }
        .font-mono { font-family: 'Fira Code', monospace; }
        ::selection { background: #691d2a; color: #fff; }
        @keyframes nfFade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        @keyframes nfSlideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
        @keyframes nfToastIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: none; } }
        .nf-fade { animation: nfFade .4s ease; }
    </style>
    @include('storefront.partials.pixels')
    @stack('head')
</head>
<body class="antialiased min-h-screen flex flex-col"
      x-data="{
        cartOpen: {{ session('open_cart') ? 'true' : 'false' }},
        menuOpen: false,
        toasts: [],
        pushToast(type, msg) { const id = Date.now()+Math.random(); this.toasts.push({id,type,msg}); setTimeout(() => this.remove(id), 4200); },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); }
      }"
      x-init="
        @if(session('success')) pushToast('success', @js(session('success'))); @endif
        @if(session('error')) pushToast('error', @js(session('error'))); @endif
      "
      @cart:toast.window="pushToast($event.detail.type, $event.detail.msg)"
      @cart:open.window="cartOpen = true">

    @include('storefront.partials.header')

    {{-- Mobile menu --}}
    <div x-show="menuOpen" x-cloak @click="menuOpen=false" @keydown.escape.window="menuOpen=false"
         class="fixed inset-0 z-[55] bg-[#3c1018]/40"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click.stop x-show="menuOpen"
             class="absolute top-0 left-0 h-full w-[280px] max-w-[82vw] bg-[#fff2e3] shadow-2xl flex flex-col"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
            <div class="flex items-center justify-between px-6 py-5 border-b border-[#EADBC4]">
                <span class="font-bold text-lg tracking-[0.24em] text-[#691d2a]">NAFIAN</span>
                <button @click="menuOpen=false" class="text-[#691d2a]">✕</button>
            </div>
            @php
                $mActiveCat = optional(request()->route('category'))->slug;
                $mNavItems = [
                    ['label' => 'Home', 'url' => route('store.home'), 'active' => request()->routeIs('store.home')],
                    ['label' => 'New In', 'url' => route('store.shop'), 'active' => request()->routeIs('store.shop')],
                    ['label' => 'Bags', 'url' => route('store.shop.category', 'bags'), 'active' => $mActiveCat === 'bags'],
                    ['label' => 'Apparel', 'url' => route('store.shop.category', 'apparel'), 'active' => $mActiveCat === 'apparel'],
                    ['label' => 'Accessories', 'url' => route('store.shop.category', 'accessories'), 'active' => $mActiveCat === 'accessories'],
                ];
            @endphp
            <div class="flex flex-col p-3 gap-1 text-[17px] font-semibold">
                @foreach($mNavItems as $item)
                    <a href="{{ $item['url'] }}" @class([
                        'px-4 py-3.5 rounded-lg transition-colors',
                        'bg-[#F8EAD6] text-[#691d2a]' => $item['active'],
                        'hover:bg-[#F8EAD6]' => ! $item['active'],
                    ])>{{ $item['label'] }}</a>
                @endforeach
                <a href="{{ route('store.shop') }}" class="px-4 py-3.5 rounded-lg hover:bg-[#F8EAD6] text-[#B45309]">Sale</a>
                <div class="h-px bg-[#EADBC4] mx-4 my-2"></div>
                <a href="{{ auth('web')->check() ? route('store.account.orders') : route('login') }}" class="px-4 py-3.5 rounded-lg hover:bg-[#F8EAD6]">My account</a>
            </div>
        </div>
    </div>

    <main class="flex-1">
        @yield('content')
    </main>

    @include('storefront.partials.footer')
    @include('storefront.partials.cart-drawer')
    @include('storefront.partials.chat-widget')

    {{-- Toasts --}}
    <div class="fixed top-[18px] right-[18px] z-[80] flex flex-col gap-2.5 pointer-events-none">
        <template x-for="t in toasts" :key="t.id">
            <div class="pointer-events-auto min-w-[260px] max-w-[340px] bg-white border border-[#EADBC4] rounded-[9px] shadow-xl p-3.5 flex gap-3 items-start"
                 :style="`border-left:3px solid ${t.type==='error' ? '#DC2626' : '#16A34A'}`"
                 style="animation:nfToastIn .24s ease">
                <span :style="`color:${t.type==='error' ? '#DC2626' : '#16A34A'}`" x-text="t.type==='error' ? '✕' : '✓'" class="font-semibold"></span>
                <div class="flex-1 text-[13.5px] leading-snug" x-text="t.msg"></div>
                <button @click="remove(t.id)" class="text-gray-400 text-xs">✕</button>
            </div>
        </template>
    </div>
</body>
</html>
