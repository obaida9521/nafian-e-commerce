@extends('layouts.admin')
@section('title', 'ইনভেন্টরি')

@section('content')
<div
     x-data="{
        open: false, name: '', sku: '', url: '', current: 0, action: 'add', qty: 10, reason: 'New shipment received', notes: '',
        adjust(name, sku, url, current = 0) { this.name=name; this.sku=sku; this.url=url; this.current=current; this.action='add'; this.qty=10; this.reason='New shipment received'; this.notes=''; this.open=true; },
        get change() { return (this.action === 'add' ? 1 : -1) * Math.abs(parseInt(this.qty) || 0); },
        get after() { return this.current + this.change; },
        step(n) { this.qty = Math.max(1, (parseInt(this.qty) || 0) + n); }
     }">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4.5">
        <div class="flex flex-wrap gap-x-4 gap-y-1.5 text-[12.5px] sm:text-[13px] text-gray-500 order-2 lg:order-none">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#16A34A;"></span>Healthy</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#D97706;"></span>Low stock</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#DC2626;"></span>Out of stock</span>
        </div>
        <form method="GET" class="grid grid-cols-[1fr_auto] sm:flex gap-2.5 w-full sm:w-auto">
            <input name="search" value="{{ request('search') }}" placeholder="Search SKU or product…" class="col-span-2 h-[42px] sm:h-[38px] w-full sm:w-56 border border-[#E9E4E0] rounded-lg px-3 text-sm bg-white outline-none focus:border-wine-700">
            <label class="inline-flex items-center gap-2 h-[42px] sm:h-auto text-sm text-gray-700 border border-[#E9E4E0] rounded-lg bg-white px-3">
                <input type="checkbox" name="low_stock" value="1" @checked(request()->boolean('low_stock')) class="accent-wine-700"> Low only
            </label>
            <button class="inline-flex items-center justify-center gap-1.5 h-[42px] sm:h-[38px] px-4 rounded-lg bg-wine-700 hover:bg-[#2A2220] text-white text-sm font-semibold"><x-ui.icon name="filter" :size="15" />Filter</button>
        </form>
    </div>

    {{-- Table (large screens) --}}
    <div class="hidden lg:block bg-white border border-[#E9E4E0] rounded-xl overflow-hidden">
        <div class="grid grid-cols-[1.1fr_1.6fr_1fr_0.8fr_0.8fr_0.8fr_1fr_90px] px-5 py-3 text-[11px] tracking-wide uppercase text-gray-400 font-semibold border-b border-[#F0ECE9]">
            <span>SKU</span><span>Product</span><span>Variant</span><span>Stock</span><span>Reserved</span><span>Available</span><span>Updated</span><span></span>
        </div>
        @forelse($variants as $variant)
            @php
                $available = $variant->available_quantity;
                $dot = $available <= 0 ? '#DC2626' : ($available <= $threshold ? '#D97706' : '#16A34A');
            @endphp
            <div class="grid grid-cols-[1.1fr_1.6fr_1fr_0.8fr_0.8fr_0.8fr_1fr_90px] px-5 py-3.5 items-center border-b border-[#F5F2F0] text-[13.5px] hover:bg-[#FBEFDD]">
                <span class="font-mono text-xs flex items-center gap-2"><span class="w-2 h-2 rounded-full flex-none" style="background:{{ $dot }};"></span>{{ $variant->sku }}</span>
                <span class="font-semibold truncate">{{ $variant->product?->name }}</span>
                <span class="text-gray-500">{{ $variant->display_name }}</span>
                <span class="font-semibold">{{ $variant->stock_quantity }}</span>
                <span class="text-gray-500">{{ $variant->reserved_quantity }}</span>
                <span class="font-semibold" style="color:{{ $dot }};">{{ $available }}</span>
                <span class="text-gray-400 text-xs">{{ $variant->updated_at?->diffForHumans() }}</span>
                <button @click="adjust(@js($variant->product?->name), @js($variant->sku), @js(route('admin.inventory.adjust', $variant)), {{ (int) $variant->stock_quantity }})" class="inline-flex items-center gap-1.5 text-wine-700 font-semibold text-left"><x-ui.icon name="sliders" :size="14" />Adjust</button>
            </div>
        @empty
            <div class="px-5 py-12 text-center text-gray-400">No variants found.</div>
        @endforelse
    </div>


    {{-- Cards (phones / tablets) --}}
    <div class="lg:hidden grid sm:grid-cols-2 gap-3">
        @forelse($variants as $variant)
            @php
                $available = $variant->available_quantity;
                [$color, $label] = $available <= 0 ? ['#DC2626', 'স্টক শেষ'] : ($available <= $threshold ? ['#D97706', 'কম স্টক'] : ['#16A34A', 'স্টকে আছে']);
                $fill = min(100, (int) round($available / max(1, $threshold * 3) * 100));
            @endphp
            <div class="relative bg-white border border-[#E9E4E0] rounded-2xl overflow-hidden pl-4 pr-3.5 py-3.5">
                <span class="absolute inset-y-0 left-0 w-1.5" style="background:{{ $color }};"></span>

                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-semibold text-[15px] leading-snug truncate">{{ $variant->product?->name }}</div>
                        <div class="mt-0.5 text-[12.5px] text-gray-500 truncate">{{ $variant->display_name }} · <span class="font-mono text-gray-400">{{ $variant->sku }}</span></div>
                    </div>
                    <span class="flex-none text-[11.5px] font-semibold px-2.5 py-1 rounded-full" style="background:{{ $color }}1A;color:{{ $color }};">{{ $label }}</span>
                </div>

                <div class="mt-3 grid grid-cols-3 rounded-xl bg-[#FAF9F8] divide-x divide-[#F0ECE9] text-center">
                    <div class="py-2">
                        <div class="text-[11px] text-gray-400">স্টক</div>
                        <div class="text-[16px] font-semibold">{{ $variant->stock_quantity }}</div>
                    </div>
                    <div class="py-2">
                        <div class="text-[11px] text-gray-400">রিজার্ভড</div>
                        <div class="text-[16px] font-semibold text-gray-500">{{ $variant->reserved_quantity }}</div>
                    </div>
                    <div class="py-2">
                        <div class="text-[11px] text-gray-400">বিক্রিযোগ্য</div>
                        <div class="text-[18px] font-bold leading-tight" style="color:{{ $color }};">{{ $available }}</div>
                    </div>
                </div>
                <div class="mt-2.5 h-1.5 rounded-full bg-[#F0ECE9] overflow-hidden" title="সতর্কসীমা: {{ $threshold }}">
                    <div class="h-full rounded-full" style="width:{{ $fill }}%;background:{{ $color }};"></div>
                </div>

                <div class="mt-3 flex items-center justify-between gap-3">
                    <span class="text-[12px] text-gray-400">{{ $variant->updated_at?->diffForHumans() }}</span>
                    <button @click="adjust(@js($variant->product?->name), @js($variant->sku), @js(route('admin.inventory.adjust', $variant)), {{ (int) $variant->stock_quantity }})"
                            class="h-9 px-4 rounded-xl bg-wine-700 hover:bg-[#2A2220] text-white text-[13.5px] font-semibold inline-flex items-center gap-1.5">
                        <x-ui.icon name="sliders" :size="15" />Adjust
                    </button>
                </div>
            </div>
        @empty
            <div class="sm:col-span-2 bg-white border border-[#E9E4E0] rounded-2xl px-5 py-12 text-center text-gray-400">No variants found.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $variants->links() }}</div>

    {{-- Adjust modal --}}
    <div x-show="open" x-cloak @keydown.escape.window="open=false" class="fixed inset-0 z-[75] flex items-end sm:items-center justify-center">
        <div @click="open=false" class="absolute inset-0 bg-[#1A1413]/40"></div>
        <form method="POST" :action="url" x-show="open"
              x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full sm:translate-y-0 sm:opacity-0" x-transition:enter-end="translate-y-0 sm:opacity-100"
              class="relative bg-white w-full sm:w-[420px] sm:max-w-[92vw] rounded-t-[22px] sm:rounded-[14px] p-5 sm:p-6.5 pb-[max(20px,env(safe-area-inset-bottom))] shadow-2xl max-h-[92vh] overflow-y-auto">
            <div class="sm:hidden w-10 h-1 rounded bg-[#E2DBD6] mx-auto -mt-1 mb-4"></div>
            @csrf
            <input type="hidden" name="quantity_change" :value="change">
            <input type="hidden" name="reason" :value="notes ? `${reason} — ${notes}` : reason">
            <div class="text-lg font-semibold mb-1">Adjust stock</div>
            <div class="text-[13px] text-gray-500 mb-5"><span x-text="name"></span> · <span class="font-mono" x-text="sku"></span></div>
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-[13px] text-gray-500 mb-1.5">Adjustment</label>
                    <select x-model="action" class="w-full h-[42px] border border-[#E9E4E0] rounded-[7px] px-3 text-sm bg-white outline-none focus:border-wine-700">
                        <option value="add">+ Add stock</option>
                        <option value="remove">− Remove stock</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[13px] text-gray-500 mb-1.5">Quantity</label>
                    <div class="flex h-[42px] border border-[#E9E4E0] rounded-[7px] overflow-hidden focus-within:border-wine-700">
                        <button type="button" @click="step(-1)" class="w-10 flex-none grid place-items-center text-gray-600 hover:bg-[#FAF9F8]" aria-label="কমান">−</button>
                        <input type="number" min="1" x-model="qty" class="w-full min-w-0 text-center text-sm outline-none">
                        <button type="button" @click="step(1)" class="w-10 flex-none grid place-items-center text-gray-600 hover:bg-[#FAF9F8]" aria-label="বাড়ান">+</button>
                    </div>
                </div>
            </div>
            <div class="mb-4 flex items-center justify-between rounded-xl bg-[#FAF9F8] px-4 py-3 text-[13.5px]">
                <span class="text-gray-500">স্টক</span>
                <span class="font-semibold">
                    <span x-text="current"></span>
                    <span class="mx-1.5 text-gray-400">→</span>
                    <span :class="after < 0 ? 'text-red-600' : (change >= 0 ? 'text-green-700' : 'text-amber-700')" x-text="after"></span>
                </span>
            </div>
            <p x-show="after < 0" x-cloak class="-mt-2 mb-4 text-[12.5px] text-red-600">স্টকের চেয়ে বেশি সরানো যাবে না।</p>
            <div class="mb-4">
                <label class="block text-[13px] text-gray-500 mb-1.5">Reason</label>
                <select x-model="reason" class="w-full h-[42px] border border-[#E9E4E0] rounded-[7px] px-3 text-sm bg-white outline-none focus:border-wine-700">
                    <option>New shipment received</option>
                    <option>Damaged / write-off</option>
                    <option>Stock count correction</option>
                    <option>Returned to supplier</option>
                </select>
            </div>
            <div class="mb-5.5">
                <label class="block text-[13px] text-gray-500 mb-1.5">Notes <span class="text-gray-300">(optional)</span></label>
                <input x-model="notes" placeholder="Add a note…" class="w-full h-[42px] border border-[#E9E4E0] rounded-[7px] px-3.5 text-sm outline-none focus:border-wine-700">
            </div>
            <div class="flex justify-end gap-2.5">
                <button type="button" @click="open=false" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 h-[42px] px-5 border border-[#E9E4E0] rounded-lg bg-white text-gray-700 text-sm font-semibold"><x-ui.icon name="x" :size="15" />Cancel</button>
                <button :disabled="after < 0 || change === 0" class="flex-[2] sm:flex-none inline-flex items-center justify-center gap-1.5 h-[42px] px-5 rounded-lg bg-wine-700 hover:bg-[#2A2220] text-white text-sm font-semibold disabled:opacity-40 disabled:cursor-not-allowed"><x-ui.icon name="check" :size="15" />Apply adjustment</button>
            </div>
        </form>
    </div>
</div>
@endsection
