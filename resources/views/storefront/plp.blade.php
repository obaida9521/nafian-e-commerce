@extends('layouts.app')
@section('title', $title)

@section('content')
<div class="max-w-[1280px] mx-auto px-4 sm:px-6 pt-7 pb-[72px] nf-fade"
     x-data="{
        sort: @js($sort),
        max: {{ $maxPrice }},
        colors: @js(array_values($selectedColors)),
        apply() {
            const u = new URL(window.location.href.split('?')[0], window.location.origin);
            u.searchParams.set('sort', this.sort);
            u.searchParams.set('max', this.max);
            this.colors.forEach(c => u.searchParams.append('colors[]', c));
            window.location = u.toString();
        },
        toggleColor(c) { this.colors.includes(c) ? this.colors = this.colors.filter(x=>x!==c) : this.colors.push(c); this.apply(); }
     }">
    <div class="text-xs text-gray-400 mb-2"><a href="{{ route('store.home') }}">Home</a> · {{ $title }}</div>
    <h1 class="text-3xl font-semibold tracking-tight mb-6">{{ $title }}</h1>

    <div class="grid lg:grid-cols-[230px_1fr] gap-10 items-start">
        {{-- Filters --}}
        <aside class="hidden lg:block sticky top-[78px]">
            <div class="text-xs tracking-[0.1em] uppercase text-gray-400 font-semibold mb-3.5">Category</div>
            <div class="flex flex-col gap-3 mb-7.5">
                <a href="{{ route('store.shop') }}" class="flex items-center gap-2.5 text-sm {{ $activeCategory ? 'text-gray-700' : 'text-[#691d2a] font-semibold' }}">
                    <span class="w-3.5 h-3.5 rounded-full border-[1.5px] border-[#C9C3B5] flex-none" style="background:{{ $activeCategory ? 'transparent' : '#691d2a' }};"></span>All products
                </a>
                @foreach($categories as $c)
                    <a href="{{ route('store.shop.category', $c->slug) }}" class="flex items-center gap-2.5 text-sm {{ $activeCategory?->id === $c->id ? 'text-[#691d2a] font-semibold' : 'text-gray-700' }}">
                        <span class="w-3.5 h-3.5 rounded-full border-[1.5px] border-[#C9C3B5] flex-none" style="background:{{ $activeCategory?->id === $c->id ? '#691d2a' : 'transparent' }};"></span>{{ $c->name }}
                    </a>
                @endforeach
            </div>

            <div class="text-xs tracking-[0.1em] uppercase text-gray-400 font-semibold mb-3.5">Price</div>
            <input type="range" min="50" max="500" step="5" x-model="max" @change="apply()" class="w-full mb-1.5" style="accent-color:#691d2a;">
            <div class="flex justify-between text-[13px] text-gray-500 mb-7.5"><span>৳50</span><span class="font-semibold text-gray-900">Up to ৳<span x-text="max"></span></span></div>

            <div class="text-xs tracking-[0.1em] uppercase text-gray-400 font-semibold mb-3.5">Colour</div>
            <div class="flex flex-wrap gap-2.5 mb-6.5">
                @foreach($colorOptions as $name)
                    <button @click="toggleColor(@js($name))" title="{{ $name }}" class="w-[26px] h-[26px] rounded-full"
                            style="background:{{ color_hex($name) }};box-shadow:0 0 0 2px #fff, 0 0 0 3.5px {{ in_array($name, $selectedColors) ? '#691d2a' : '#E3D8C4' }};"></button>
                @endforeach
            </div>
            <a href="{{ route('store.shop') }}" class="text-[13px] text-gray-500 underline">Clear all filters</a>
        </aside>

        {{-- Grid --}}
        <div>
            <div class="flex items-center justify-between mb-4.5">
                <span class="text-sm text-gray-500">{{ $products->count() }} products</span>
                <select x-model="sort" @change="apply()" class="h-10 border border-[#EADBC4] rounded-[7px] px-3 text-sm bg-white text-gray-700 outline-none">
                    @foreach(['Featured','Price: Low to High','Price: High to Low','Top Rated'] as $opt)
                        <option @selected($sort === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>

            @if(!empty($selectedColors))
                <div class="flex flex-wrap gap-2 mb-5">
                    @foreach($selectedColors as $c)
                        <button @click="toggleColor(@js($c))" class="inline-flex items-center gap-1.5 bg-white border border-[#EADBC4] rounded-[20px] px-3 py-1.5 text-[13px] text-gray-700">{{ $c }} <span class="text-gray-400">✕</span></button>
                    @endforeach
                </div>
            @endif

            @if($products->isEmpty())
                <div class="py-24 text-center text-gray-400 font-mono text-sm">No products match these filters.</div>
            @else
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($products as $product)
                        @include('storefront.partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
