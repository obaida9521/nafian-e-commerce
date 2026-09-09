<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('shop.name') }} Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="antialiased" x-data="{ sidebarOpen: false }">
<div class="min-h-screen">

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside
        class="fixed inset-y-0 left-0 z-40 w-[240px] bg-[#4d141e] text-white flex flex-col transition-transform duration-200 lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

        <div class="h-16 flex items-center gap-2.5 px-5 border-b border-white/10">
            @if($logo = brand_logo())
                <span class="w-9 h-9 rounded-xl bg-white grid place-items-center overflow-hidden p-1 flex-none">
                    <img src="{{ $logo }}" alt="{{ config('shop.name') }}" class="max-w-full max-h-full object-contain">
                </span>
            @endif
            <span class="text-lg font-bold tracking-[0.22em]">NAFIAN</span>
            <span class="text-[10px] tracking-[0.08em] uppercase text-[#b78c91] border border-[#6e2f38] rounded px-1.5 py-0.5">Admin</span>
        </div>

        <nav class="flex-1 overflow-y-auto scrollbar-thin px-3 py-4 space-y-1 text-sm">
            @php
                $nav = [
                    ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['route' => 'admin.products.index', 'label' => 'Products', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                    ['route' => 'admin.categories.index', 'label' => 'Categories', 'icon' => 'M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 8V3a1 1 0 011-1z'],
                    ['route' => 'admin.orders.index', 'label' => 'Orders', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                    ['route' => 'admin.pos.index', 'label' => 'POS', 'icon' => 'M9 14h6m-3-3v6m-7 4h14a2 2 0 002-2V8a2 2 0 00-2-2h-3.5l-1-2h-5l-1 2H5a2 2 0 00-2 2v11a2 2 0 002 2z'],
                    ['route' => 'admin.inventory.index', 'label' => 'Inventory', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10'],
                    ['route' => 'admin.expenses.index', 'label' => 'Expenses', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m3-5h9a2 2 0 012 2v6a2 2 0 01-2 2h-9a2 2 0 01-2-2v-6a2 2 0 012-2zm7 5a2 2 0 11-4 0 2 2 0 014 0z'],
                    ['route' => 'admin.customers.index', 'label' => 'Customers', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z'],
                    ['route' => 'admin.coupons.index', 'label' => 'Coupons', 'icon' => 'M15 5l-1.5 1.5M9 19l1.5-1.5M3 8a2 2 0 012-2h14a2 2 0 012 2v2a2 2 0 000 4v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2a2 2 0 000-4V8z'],
                    ['route' => 'admin.reports.index', 'label' => 'Reports', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ['route' => 'admin.settings.index', 'label' => 'Settings', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                ];
            @endphp

            @foreach ($nav as $item)
                @php $active = request()->routeIs(str_replace('.index', '', $item['route']).'*'); @endphp
                <a href="{{ route($item['route']) }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ $active ? 'bg-white/10 text-white font-medium' : 'text-white/70 hover:bg-white/[0.06] hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                    </svg>
                    {{ $item['label'] }}
                </a>
            @endforeach

            <div class="h-px bg-[#5a1f28] mx-2 my-2.5"></div>
            <a href="{{ route('store.home') }}" target="_blank"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/70 hover:bg-white/[0.06] hover:text-white transition">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z M3.6 9h16.8 M3.6 15h16.8 M12 3a15 15 0 010 18 15 15 0 010-18z"/>
                </svg>
                View storefront
            </a>
        </nav>

        @php $sbAdmin = auth('admin')->user(); @endphp
        <div class="border-t border-[#5a1f28] p-3 flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-[#6e2f38] grid place-items-center text-[13px] font-semibold flex-none">
                {{ strtoupper(substr($sbAdmin?->name ?? 'A', 0, 1)) }}
            </div>
            <div class="text-[13px] leading-tight flex-1 min-w-0">
                <div class="font-semibold truncate">{{ $sbAdmin?->name }}</div>
                <div class="text-[#b78c91] text-[11px]">{{ $sbAdmin?->role?->label() }}</div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button title="Sign out" class="p-1.5 rounded-md text-white/60 hover:bg-white/10 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="lg:ml-[240px] min-h-screen flex flex-col">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 h-16 bg-white border-b border-[#EADBC4] flex items-center gap-4 sm:gap-6 px-4 sm:px-7">
            <button @click="sidebarOpen = true" class="lg:hidden p-2 -ml-2 text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <h1 class="text-xl font-semibold text-gray-900 whitespace-nowrap">@yield('title', 'Dashboard')</h1>

            <div class="flex-1 max-w-[420px] relative hidden md:block">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                </span>
                <input placeholder="Search orders, products, customers…"
                    class="w-full h-10 border border-[#EADBC4] rounded-lg pl-10 pr-3 text-sm bg-[#fff2e3] outline-none focus:bg-white focus:border-wine-700">
            </div>

            <div class="ml-auto flex items-center gap-3" x-data="{ open: false }">
                <button class="p-2 rounded-lg text-gray-500 hover:bg-cream-200/60 relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </button>

                <div class="relative">
                    @php $admin = auth('admin')->user(); @endphp
                    <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-cream-200/60">
                        <div class="w-8 h-8 rounded-full bg-wine-700 text-cream-100 grid place-items-center text-sm font-semibold">
                            {{ strtoupper(substr($admin->name, 0, 1)) }}
                        </div>
                        <div class="hidden sm:block text-left leading-tight">
                            <div class="text-sm font-medium text-gray-900">{{ $admin->name }}</div>
                            <div class="text-xs text-gray-500">{{ $admin->role->label() }}</div>
                        </div>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false"
                        class="absolute right-0 mt-2 w-48 rounded-lg bg-white shadow-lg border border-gray-100 py-1 text-sm">
                        <div class="px-4 py-2 text-gray-500 border-b border-gray-100">{{ $admin->email }}</div>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50">Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash --}}
        <div class="px-4 sm:px-6 pt-4 space-y-3">
            @if (session('success'))
                <x-ui.alert type="success">{{ session('success') }}</x-ui.alert>
            @endif
            @if (session('error'))
                <x-ui.alert type="error">{{ session('error') }}</x-ui.alert>
            @endif
            @if ($errors->any())
                <x-ui.alert type="error">{{ $errors->first() }}</x-ui.alert>
            @endif
        </div>

        <main class="flex-1 p-4 sm:p-6">
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
