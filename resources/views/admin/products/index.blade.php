@extends('layouts.admin')
@section('title', 'Products')

@section('content')
<div class="max-w-[1280px] mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4.5">
        <form method="GET" class="flex gap-2.5">
            <select name="category" onchange="this.form.submit()" class="h-[38px] border border-[#EADBC4] rounded-lg px-3 text-sm bg-white cursor-pointer outline-none focus:border-wine-700">
                <option value="">All categories</option>
                @foreach($categories as $c)
                    <option value="{{ $c->slug }}" @selected(request('category') === $c->slug)>{{ $c->name }}</option>
                @endforeach
            </select>
            <input name="search" value="{{ request('search') }}" placeholder="Search products…" class="h-[38px] w-56 border border-[#EADBC4] rounded-lg px-3 text-sm bg-white outline-none focus:border-wine-700">
            <select name="status" onchange="this.form.submit()" class="h-[38px] border border-[#EADBC4] rounded-lg px-3 text-sm bg-white cursor-pointer outline-none focus:border-wine-700">
                <option value="">Any status</option>
                <option value="active" @selected(request('status')==='active')>Active</option>
                <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
            </select>
        </form>
        @adminCan('products')
        <a href="{{ route('admin.products.create') }}" class="h-10 px-4.5 rounded-lg bg-wine-700 hover:bg-[#4d141e] text-white text-sm font-semibold flex items-center gap-2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>Add product
        </a>
        @endadminCan
    </div>

    <div class="bg-white border border-[#EADBC4] rounded-xl overflow-hidden">
        <div class="grid grid-cols-[56px_2fr_1fr_1.1fr_0.9fr_0.8fr_84px_56px] px-5 py-3 text-[11px] tracking-wide uppercase text-gray-400 font-semibold border-b border-[#EFE2CE] items-center">
            <span></span><span>Product</span><span>Category</span><span>Variants</span><span>Status</span><span>Stock</span><span class="text-center">Featured</span><span></span>
        </div>
        @forelse($products as $product)
            @php
                $stock = $product->total_stock;
                $stockDot = $stock <= 0 ? '#DC2626' : ($stock <= config('shop.low_stock_threshold') ? '#D97706' : '#16A34A');
                $sku = $product->variants->first()?->sku;
                $img = $product->getFirstMediaUrl('images', 'thumb') ?: $product->getFirstMediaUrl('images');
            @endphp
            <div class="grid grid-cols-[56px_2fr_1fr_1.1fr_0.9fr_0.8fr_84px_56px] px-5 py-3 items-center border-b border-[#F6EAD8] text-[13.5px] hover:bg-[#FBEFDD]">
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
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="{{ $product->is_featured ? '#D4A853' : 'none' }}" stroke="{{ $product->is_featured ? '#D4A853' : '#C9C3B5' }}" stroke-width="1.6"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9 6.8 19.2l1-5.8L3.5 9.2l5.9-.9L12 3Z"/></svg>
                        </button>
                    </form>
                </span>
                <a href="{{ route('admin.products.edit', $product) }}" class="text-wine-700 font-semibold">Edit</a>
            </div>
        @empty
            <div class="px-5 py-12 text-center text-gray-400">No products found.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
</div>
@endsection
