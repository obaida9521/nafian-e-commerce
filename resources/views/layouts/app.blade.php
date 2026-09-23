<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">
    <title>@yield('title', 'সকাল পর্যন্ত থেকে যায় যে ঘ্রাণ') — {{ config('shop.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Libre+Caslon+Display&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        ::selection { background: #3B2F2D; color: #fff; }
        @keyframes nfFade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        @keyframes nfToastIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: none; } }
        .nf-fade { animation: nfFade .4s ease; }
    </style>
    @include('storefront.partials.pixels')
    @stack('head')
</head>
<body class="min-h-screen flex flex-col bg-white text-ink"
      x-data="{
        cartOpen: {{ session('open_cart') ? 'true' : 'false' }},
        searchOpen: false,
        toasts: [],
        pushToast(type, msg) { const id = Date.now() + Math.random(); this.toasts.push({ id, type, msg }); setTimeout(() => this.remove(id), 4200); },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); }
      }"
      x-init="
        @if(session('success')) pushToast('success', @js(session('success'))); @endif
        @if(session('error')) pushToast('error', @js(session('error'))); @endif
      "
      @cart:toast.window="pushToast($event.detail.type, $event.detail.msg)"
      @cart:open.window="cartOpen = true"
      @keydown.escape.window="searchOpen = false; cartOpen = false">

    {{-- Announcement bar (desktop / tablet) --}}
    <div class="hidden sm:flex bg-espresso text-[#EDE6E1] items-center justify-center gap-6 px-5 py-[11px] text-[13.5px] flex-wrap text-center">
        @hasSection('announcement')
            @yield('announcement')
        @else
            <span>ঢাকায় ২৪ ঘণ্টায় ডেলিভারি</span><span class="opacity-40">•</span><span>ক্যাশ অন ডেলিভারি</span>
        @endif
    </div>

    @include('storefront.partials.header')

    <main class="flex-1 w-full max-w-[1440px] mx-auto">
        @yield('content')
    </main>

    @unless(View::hasSection('no_footer'))
        @include('storefront.partials.footer')
    @endunless

    {{-- Phones: keep the page end (footer included) clear of the fixed tab bar / sticky action bar. --}}
    @php
        $hasTabbar = ! View::hasSection('no_tabbar');
        $hasActionBar = View::hasSection('mobile_action_bar');
    @endphp
    @if($hasTabbar || $hasActionBar)
        <div aria-hidden="true" class="sm:hidden flex-none"
             style="height: calc({{ $hasTabbar ? 'var(--nf-tabbar-h)' : 'env(safe-area-inset-bottom)' }} + {{ $hasActionBar ? '80px' : '0px' }})"></div>
    @endif

    @if($hasTabbar)
        @include('storefront.partials.tabbar')
    @endif

    @include('storefront.partials.search-overlay')
    @include('storefront.partials.cart-drawer')
    @include('storefront.partials.chat-widget')

    {{-- Toasts --}}
    <div class="fixed top-4 inset-x-4 md:inset-x-auto md:right-5 md:top-5 z-[90] flex flex-col items-center md:items-end gap-2.5 pointer-events-none">
        <template x-for="t in toasts" :key="t.id">
            <div class="pointer-events-auto w-full md:w-auto md:min-w-[280px] max-w-[380px] rounded-2xl px-[18px] py-3.5 flex gap-3 items-start shadow-[0_18px_40px_-18px_rgba(36,28,26,.55)]"
                 :class="t.type === 'error' ? 'bg-rose-soft text-rose' : 'bg-espresso text-white'"
                 style="animation:nfToastIn .24s ease">
                <span x-text="t.type === 'error' ? '!' : '✓'" class="font-semibold"></span>
                <div class="flex-1 text-[14.5px] leading-snug" x-text="t.msg"></div>
                <button @click="remove(t.id)" class="opacity-60 text-xs" aria-label="বন্ধ করুন">✕</button>
            </div>
        </template>
    </div>
</body>
</html>
