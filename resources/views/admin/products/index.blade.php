@extends('layouts.admin')
@section('title', 'পণ্য')

@section('content')
<div>
    <div class="flex flex-wrap items-center justify-between gap-3 sm:gap-4 mb-4.5">
        <form method="GET" class="grid grid-cols-2 sm:flex gap-2.5 w-full sm:w-auto">
            <select name="category" onchange="this.form.submit()" class="h-[42px] sm:h-[38px] w-full sm:w-auto border border-[#E9E4E0] rounded-lg px-3 text-sm bg-white cursor-pointer outline-none focus:border-wine-700">
                <option value="">All categories</option>
                @foreach($categories as $c)
                    <option value="{{ $c->slug }}" @selected(request('category') === $c->slug)>{{ $c->name }}</option>
                @endforeach
            </select>
            <input name="search" value="{{ request('search') }}" placeholder="Search products…" class="col-span-2 order-first sm:order-none h-[42px] sm:h-[38px] w-full sm:w-56 border border-[#E9E4E0] rounded-lg px-3 text-sm bg-white outline-none focus:border-wine-700">
            <select name="status" onchange="this.form.submit()" class="h-[42px] sm:h-[38px] w-full sm:w-auto border border-[#E9E4E0] rounded-lg px-3 text-sm bg-white cursor-pointer outline-none focus:border-wine-700">
                <option value="">Any status</option>
                <option value="active" @selected(request('status')==='active')>Active</option>
                <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
            </select>
        </form>
        @adminCan('products')
        <a href="{{ route('admin.products.create') }}" class="h-[42px] sm:h-10 w-full sm:w-auto px-4.5 rounded-lg bg-wine-700 hover:bg-[#2A2220] text-white text-sm font-semibold flex items-center justify-center gap-2">
            <x-ui.icon name="plus" :size="16" />পণ্য যোগ করুন
        </a>
        @endadminCan
    </div>

    {{-- Table (large screens) --}}
    <div class="hidden lg:block bg-white border border-[#E9E4E0] rounded-xl overflow-hidden">
        <div class="grid grid-cols-[56px_2fr_1fr_1.1fr_0.9fr_0.8fr_84px_56px] px-5 py-3 text-[11px] tracking-wide uppercase text-gray-400 font-semibold border-b border-[#F0ECE9] items-center">
            <span></span><span>Product</span><span>Category</span><span>Variants</span><span>Status</span><span>Stock</span><span class="text-center">Featured</span><span></span>
        </div>
        @forelse($products as $product)
            @php
                $stock = $product->total_stock;
                $stockDot = $stock <= 0 ? '#DC2626' : ($stock <= config('shop.low_stock_threshold') ? '#D97706' : '#16A34A');
                $sku = $product->variants->first()?->sku;
                $img = $product->getFirstMediaUrl('images', 'thumb') ?: $product->getFirstMediaUrl('images');
            @endphp
            <div class="grid grid-cols-[56px_2fr_1fr_1.1fr_0.9fr_0.8fr_84px_56px] px-5 py-3 items-center border-b border-[#F5F2F0] text-[13.5px] hover:bg-[#FBEFDD]">
                <div class="w-10 h-12 rounded-[7px] overflow-hidden" style="background:linear-gradient(155deg,{{ $product->tone ?? '#C2BBB0' }},{{ $product->tone2 ?? '#A39B8E' }});">
                    @if($img)<img src="{{ $img }}" alt="{{ $product->name }}" class="w-full h-full object-cover">@endif
                </div>
                <div class="min-w-0"><div class="font-semibold truncate">{{ $product->name }}</div><div class="text-xs text-gray-400 font-mono">{{ $sku ?? $product->slug }}</div></div>
                <span class="text-gray-500 truncate">{{ $product->categories->pluck('name')->join(', ') ?: '—' }}</span>
                <span class="text-gray-500">{{ $product->variants_count }} {{ Str::plural('variant', $product->variants_count) }}</span>
                <span><span class="text-[11px] font-semibold px-2.5 py-[3px] rounded-full" style="background:{{ $product->is_active ? '#DCFCE7' : '#F3F4F6' }};color:{{ $product->is_active ? '#166534' : '#374151' }};">{{ $product->is_active ? 'Active' : 'Inactive' }}</span></span>
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background:{{ $stockDot }};"></span><span class="font-semibold">{{ $stock }}</span></span>
                <span class="flex justify-center">
                    <form method="POST" action="{{ route('admin.products.toggle-featured', $product) }}">
                        @csrf @method('PATCH')
                        <button title="Toggle featured" class="block">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="{{ $product->is_featured ? '#D4A853' : 'none' }}" stroke="{{ $product->is_featured ? '#D4A853' : '#D9CDC6' }}" stroke-width="1.6"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9 6.8 19.2l1-5.8L3.5 9.2l5.9-.9L12 3Z"/></svg>
                        </button>
                    </form>
                </span>
                <a href="{{ route('admin.products.edit', $product) }}" class="inline-flex items-center gap-1.5 text-wine-700 font-semibold"><x-ui.icon name="edit" :size="14" />Edit</a>
            </div>
        @empty
            <div class="px-5 py-12 text-center text-gray-400">কোনো পণ্য পাওয়া যায়নি।</div>
        @endforelse
    </div>


    {{-- Cards (phones / tablets) --}}
    <div class="lg:hidden grid sm:grid-cols-2 gap-3">
        @forelse($products as $product)
            @php
                $stock = $product->total_stock;
                $low = config('shop.low_stock_threshold');
                [$stockColor, $stockLabel] = $stock <= 0 ? ['#DC2626', 'স্টক শেষ'] : ($stock <= $low ? ['#D97706', 'কম স্টক'] : ['#16A34A', 'স্টকে আছে']);
                $sku = $product->variants->first()?->sku;
                $img = $product->getFirstMediaUrl('images', 'thumb') ?: $product->getFirstMediaUrl('images');
                $minPrice = $product->variants->min('price');
                $editUrl = route('admin.products.edit', $product);
            @endphp
            <div class="relative bg-white border border-[#E9E4E0] rounded-2xl p-3.5 flex flex-col gap-3">
                <div class="flex gap-3">
                    <a href="{{ $editUrl }}" class="flex-none w-[72px] h-[84px] rounded-xl overflow-hidden" style="background:linear-gradient(155deg,{{ $product->tone ?? '#C2BBB0' }},{{ $product->tone2 ?? '#A39B8E' }});">
                        @if($img)<img src="{{ $img }}" alt="{{ $product->name }}" loading="lazy" class="w-full h-full object-cover">@endif
                    </a>
                    <div class="min-w-0 flex-1 pr-8">
                        <a href="{{ $editUrl }}" class="block font-semibold text-[15px] leading-snug line-clamp-2">{{ $product->name }}</a>
                        <div class="mt-0.5 text-xs text-gray-400 font-mono truncate">{{ $sku ?? $product->slug }}</div>
                        <div class="mt-1 text-[12.5px] text-gray-500 truncate">{{ $product->categories->pluck('name')->join(', ') ?: '—' }}</div>
                    </div>
                </div>

                {{-- Featured toggle --}}
                <form method="POST" action="{{ route('admin.products.toggle-featured', $product) }}" class="absolute top-2.5 right-2.5">
                    @csrf @method('PATCH')
                    <button title="Toggle featured" aria-label="ফিচার্ড" class="w-9 h-9 grid place-items-center rounded-full hover:bg-[#FAF9F8]">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="{{ $product->is_featured ? '#D4A853' : 'none' }}" stroke="{{ $product->is_featured ? '#D4A853' : '#D9CDC6' }}" stroke-width="1.6"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9 6.8 19.2l1-5.8L3.5 9.2l5.9-.9L12 3Z"/></svg>
                    </button>
                </form>

                <div class="flex flex-wrap gap-1.5 text-[12px]">
                    <span class="font-semibold px-2.5 py-1 rounded-full" style="background:{{ $product->is_active ? '#DCFCE7' : '#F3F4F6' }};color:{{ $product->is_active ? '#166534' : '#374151' }};">{{ $product->is_active ? 'Active' : 'Inactive' }}</span>
                    <span class="px-2.5 py-1 rounded-full bg-[#F6F3F1] text-gray-600">{{ $product->variants_count }} {{ Str::plural('variant', $product->variants_count) }}</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#F6F3F1] text-gray-600">
                        <span class="w-2 h-2 rounded-full" style="background:{{ $stockColor }};"></span>{{ $stockLabel }} · <span class="font-semibold text-gray-800">{{ $stock }}</span>
                    </span>
                    @if($minPrice !== null)
                        <span class="px-2.5 py-1 rounded-full bg-[#F6F3F1] text-gray-600">{{ config('shop.currency_symbol') }}{{ number_format((float) $minPrice) }}{{ $product->variants_count > 1 ? '+' : '' }}</span>
                    @endif
                </div>

                <a href="{{ $editUrl }}" class="h-10 rounded-xl border border-[#E9E4E0] text-wine-700 text-[14px] font-semibold inline-flex items-center justify-center gap-1.5 hover:border-wine-700">
                    <x-ui.icon name="edit" :size="15" />Edit
                </a>
            </div>
        @empty
            <div class="sm:col-span-2 bg-white border border-[#E9E4E0] rounded-2xl px-5 py-12 text-center text-gray-400">কোনো পণ্য পাওয়া যায়নি।</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
</div>
@endsection
