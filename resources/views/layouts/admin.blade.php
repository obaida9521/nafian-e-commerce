<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ড্যাশবোর্ড') — {{ config('shop.name') }} অ্যাডমিন</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Libre+Caslon+Display&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
@php
    $sbAdmin = auth('admin')->user();
    $pendingOrders = \App\Models\Order::where('status', \App\Enums\OrderStatus::Pending)->count();
    $nav = [
        ['route' => 'admin.dashboard', 'label' => 'ড্যাশবোর্ড', 'match' => 'admin.dashboard'],
        ['route' => 'admin.orders.index', 'label' => 'অর্ডার', 'match' => 'admin.orders.*', 'badge' => $pendingOrders],
        ['route' => 'admin.products.index', 'label' => 'পণ্য', 'match' => 'admin.products.*'],
        ['route' => 'admin.categories.index', 'label' => 'ক্যাটাগরি', 'match' => 'admin.categories.*'],
        ['route' => 'admin.media.index', 'label' => 'মিডিয়া', 'match' => 'admin.media.*'],
        ['route' => 'admin.inventory.index', 'label' => 'ইনভেন্টরি', 'match' => 'admin.inventory.*'],
        ['route' => 'admin.pos.index', 'label' => 'পিওএস', 'match' => 'admin.pos.*'],
        ['route' => 'admin.customers.index', 'label' => 'গ্রাহক', 'match' => 'admin.customers.*'],
        ['route' => 'admin.coupons.index', 'label' => 'কুপন ও অফার', 'match' => 'admin.coupons.*'],
        ['route' => 'admin.expenses.index', 'label' => 'খরচ', 'match' => 'admin.expenses.*'],
        ['route' => 'admin.reports.index', 'label' => 'রিপোর্ট', 'match' => 'admin.reports.*'],
        ['route' => 'admin.activity-logs.index', 'label' => 'অ্যাক্টিভিটি লগ', 'match' => 'admin.activity-logs.*'],
        ['route' => 'admin.settings.index', 'label' => 'সেটিংস', 'match' => 'admin.settings.*'],
    ];
@endphp
<body class="bg-canvas text-ink" x-data="{ sidebarOpen: false }">
<div class="w-full grid desk:grid-cols-[248px_1fr] min-h-screen">

    {{-- Sidebar --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="desk:hidden fixed inset-0 z-40 bg-[#1A1413]/50"></div>

    <aside class="bg-mocha text-[#E8E0DB] px-5 py-[26px] flex flex-col gap-[26px] desk:sticky desk:top-0 desk:h-screen max-desk:fixed max-desk:inset-y-0 max-desk:left-0 max-desk:z-50 max-desk:w-[260px] max-desk:transition-transform"
           :class="sidebarOpen ? 'max-desk:translate-x-0' : 'max-desk:-translate-x-full'">
        <div class="flex items-start justify-between">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="font-display text-[22px] tracking-[0.3em] text-white">NAFIAN</a>
                <div class="mt-1 text-[12.5px] text-[#A2948D]">অ্যাডমিন প্যানেল</div>
            </div>
            <button @click="sidebarOpen = false" class="desk:hidden text-[#A2948D] text-lg" aria-label="বন্ধ করুন">✕</button>
        </div>

        <nav class="min-h-0 flex flex-col gap-1 text-[15px] font-medium overflow-y-auto scrollbar-thin">
            @foreach($nav as $item)
                @php $active = request()->routeIs($item['match']); @endphp
                <a href="{{ route($item['route']) }}" @class([
                    'rounded-xl px-4 py-3 flex items-center gap-1.5',
                    'bg-white/[0.12] text-white' => $active,
                    'text-[#C6BAB4] hover:text-white' => ! $active,
                ])>
                    {{ $item['label'] }}
                    @if(($item['badge'] ?? 0) > 0)
                        <span class="bg-accent text-white rounded-full px-2.5 py-0.5 text-[12px] font-semibold">{{ bn_digits($item['badge']) }}</span>
                    @endif
                </a>
            @endforeach
            <a href="{{ route('store.home') }}" target="_blank" rel="noopener" class="rounded-xl px-4 py-3 text-[#C6BAB4] hover:text-white flex items-center gap-2"><x-ui.icon name="external" :size="16" />স্টোর দেখুন</a>
        </nav>

        <div class="mt-auto bg-white/[0.08] rounded-2xl p-4">
            <div class="text-[14.5px] font-semibold text-white">{{ $sbAdmin?->name }}</div>
            <div class="mt-0.5 text-[13px] text-[#A2948D]">{{ $sbAdmin?->role?->label() }}</div>
            <form method="POST" action="{{ route('admin.logout') }}" class="mt-3">
                @csrf
                <button class="inline-flex items-center gap-1.5 text-[13px] text-[#C6BAB4] hover:text-white"><x-ui.icon name="logout" :size="14" />সাইন আউট</button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="bg-canvas px-5 sm:px-7 py-6 pb-16 min-w-0">
        {{-- Phones / tablets: sticky bar — menu button + every drawer item in one swipeable row --}}
        <div class="desk:hidden sticky top-0 z-30 -mx-5 sm:-mx-7 -mt-6 mb-[18px] px-5 sm:px-7 pt-3 pb-3 bg-canvas/95 backdrop-blur border-b border-hair flex items-center gap-2">
            <button @click="sidebarOpen = true" class="flex-none inline-flex items-center gap-1.5 bg-mocha text-white rounded-full px-[16px] py-2.5 text-[14px] font-medium" aria-label="মেনু খুলুন"><x-ui.icon name="menu" :size="16" />মেনু</button>
            <nav class="flex-1 min-w-0 flex gap-2 overflow-x-auto overscroll-x-contain [scrollbar-width:none] [&::-webkit-scrollbar]:hidden [mask-image:linear-gradient(to_right,transparent,#000_10px,#000_calc(100%-28px),transparent)] px-2.5 -mx-1"
                 aria-label="অ্যাডমিন মেনু"
                 x-init="$nextTick(() => { const a = $el.querySelector('[aria-current]'); if (a) $el.scrollLeft = a.offsetLeft - ($el.clientWidth - a.offsetWidth) / 2; })">
                @foreach($nav as $item)
                    @php $active = request()->routeIs($item['match']); @endphp
                    <a href="{{ route($item['route']) }}" @if($active) aria-current="page" @endif @class([
                        'flex-none inline-flex items-center gap-1.5 rounded-full px-[16px] py-2.5 text-[14px] font-medium whitespace-nowrap',
                        'bg-mocha text-white' => $active,
                        'bg-hair text-ink' => ! $active,
                    ])>
                        {{ $item['label'] }}
                        @if(($item['badge'] ?? 0) > 0)
                            <span class="bg-accent text-white rounded-full px-2 py-px text-[11.5px] font-semibold leading-[18px]">{{ bn_digits($item['badge']) }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- Success / error messages appear as snackbars (components/admin/toasts); the full validation list stays inline. --}}
        @if ($errors->any())
            <div class="mb-4 flex flex-col gap-2.5">
                @if ($errors->any())
                    <div class="rounded-2xl bg-rose-soft text-rose px-5 py-3.5 text-[14.5px]">
                        <ul class="flex flex-col gap-1">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        @yield('content')
    </div>
</div>

<x-admin.media-picker />
<x-admin.toasts />
</body>
</html>
