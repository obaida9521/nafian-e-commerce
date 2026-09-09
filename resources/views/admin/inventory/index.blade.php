@extends('layouts.admin')
@section('title', 'Inventory')

@section('content')
<div class="max-w-[1280px] mx-auto"
     x-data="{
        open: false, name: '', sku: '', url: '', action: 'add', qty: 10, reason: 'New shipment received', notes: '',
        adjust(name, sku, url) { this.name=name; this.sku=sku; this.url=url; this.action='add'; this.qty=10; this.reason='New shipment received'; this.notes=''; this.open=true; },
        get change() { return (this.action === 'add' ? 1 : -1) * Math.abs(parseInt(this.qty) || 0); }
     }">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4.5">
        <div class="flex gap-4 text-[13px] text-gray-500">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#16A34A;"></span>Healthy</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#D97706;"></span>Low stock</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#DC2626;"></span>Out of stock</span>
        </div>
        <form method="GET" class="flex gap-2.5">
            <input name="search" value="{{ request('search') }}" placeholder="Search SKU or product…" class="h-[38px] w-56 border border-[#EADBC4] rounded-lg px-3 text-sm bg-white outline-none focus:border-wine-700">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 border border-[#EADBC4] rounded-lg bg-white px-3">
                <input type="checkbox" name="low_stock" value="1" @checked(request()->boolean('low_stock')) class="accent-wine-700"> Low only
            </label>
            <button class="h-[38px] px-4 rounded-lg bg-wine-700 hover:bg-[#4d141e] text-white text-sm font-semibold">Filter</button>
        </form>
    </div>

    <div class="bg-white border border-[#EADBC4] rounded-xl overflow-hidden">
        <div class="grid grid-cols-[1.1fr_1.6fr_1fr_0.8fr_0.8fr_0.8fr_1fr_90px] px-5 py-3 text-[11px] tracking-wide uppercase text-gray-400 font-semibold border-b border-[#EFE2CE]">
            <span>SKU</span><span>Product</span><span>Variant</span><span>Stock</span><span>Reserved</span><span>Available</span><span>Updated</span><span></span>
        </div>
        @forelse($variants as $variant)
            @php
                $available = $variant->available_quantity;
                $dot = $available <= 0 ? '#DC2626' : ($available <= $threshold ? '#D97706' : '#16A34A');
            @endphp
            <div class="grid grid-cols-[1.1fr_1.6fr_1fr_0.8fr_0.8fr_0.8fr_1fr_90px] px-5 py-3.5 items-center border-b border-[#F6EAD8] text-[13.5px] hover:bg-[#FBEFDD]">
                <span class="font-mono text-xs flex items-center gap-2"><span class="w-2 h-2 rounded-full flex-none" style="background:{{ $dot }};"></span>{{ $variant->sku }}</span>
                <span class="font-semibold truncate">{{ $variant->product?->name }}</span>
                <span class="text-gray-500">{{ $variant->display_name }}</span>
                <span class="font-semibold">{{ $variant->stock_quantity }}</span>
                <span class="text-gray-500">{{ $variant->reserved_quantity }}</span>
                <span class="font-semibold" style="color:{{ $dot }};">{{ $available }}</span>
                <span class="text-gray-400 text-xs">{{ $variant->updated_at?->diffForHumans() }}</span>
                <button @click="adjust(@js($variant->product?->name), @js($variant->sku), @js(route('admin.inventory.adjust', $variant)))" class="text-wine-700 font-semibold text-left">Adjust</button>
            </div>
        @empty
            <div class="px-5 py-12 text-center text-gray-400">No variants found.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $variants->links() }}</div>

    {{-- Adjust modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-[75] flex items-center justify-center">
        <div @click="open=false" class="absolute inset-0 bg-[#3c1018]/40"></div>
        <form method="POST" :action="url" class="relative bg-white rounded-[14px] w-[420px] max-w-[92vw] p-6.5 shadow-2xl">
            @csrf
            <input type="hidden" name="quantity_change" :value="change">
            <input type="hidden" name="reason" :value="notes ? `${reason} — ${notes}` : reason">
            <div class="text-lg font-semibold mb-1">Adjust stock</div>
            <div class="text-[13px] text-gray-500 mb-5"><span x-text="name"></span> · <span class="font-mono" x-text="sku"></span></div>
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-[13px] text-gray-500 mb-1.5">Adjustment</label>
                    <select x-model="action" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3 text-sm bg-white outline-none focus:border-wine-700">
                        <option value="add">+ Add stock</option>
                        <option value="remove">− Remove stock</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[13px] text-gray-500 mb-1.5">Quantity</label>
                    <input type="number" min="1" x-model="qty" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-wine-700">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-[13px] text-gray-500 mb-1.5">Reason</label>
                <select x-model="reason" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3 text-sm bg-white outline-none focus:border-wine-700">
                    <option>New shipment received</option>
                    <option>Damaged / write-off</option>
                    <option>Stock count correction</option>
                    <option>Returned to supplier</option>
                </select>
            </div>
            <div class="mb-5.5">
                <label class="block text-[13px] text-gray-500 mb-1.5">Notes <span class="text-gray-300">(optional)</span></label>
                <input x-model="notes" placeholder="Add a note…" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-wine-700">
            </div>
            <div class="flex justify-end gap-2.5">
                <button type="button" @click="open=false" class="h-[42px] px-5 border border-[#EADBC4] rounded-lg bg-white text-gray-700 text-sm font-semibold">Cancel</button>
                <button class="h-[42px] px-5 rounded-lg bg-wine-700 hover:bg-[#4d141e] text-white text-sm font-semibold">Apply adjustment</button>
            </div>
        </form>
    </div>
</div>
@endsection
