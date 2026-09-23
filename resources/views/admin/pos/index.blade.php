@extends('layouts.admin')
@section('title', 'পিওএস')

@php $symbol = config('shop.currency_symbol'); @endphp

@section('content')
<div x-data="pos(@js($catalog), @js($symbol))">
    <div class="grid lg:grid-cols-[1.7fr_1fr] gap-5 items-start">

        {{-- Catalog --}}
        <div class="bg-white border border-[#E9E4E0] rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-[#F0ECE9] flex items-center gap-3">
                <div class="relative flex-1">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    </span>
                    <input x-model="search" type="text" placeholder="Search product, variant or SKU…" autofocus
                        class="w-full h-11 border border-[#E9E4E0] rounded-xl pl-10 pr-9 text-sm bg-[#FAF9F8] outline-none focus:bg-white focus:border-wine-700">
                    <button type="button" x-show="search" @click="search=''" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>
                    </button>
                </div>
                <span class="text-[12px] text-gray-400 whitespace-nowrap" x-text="filtered.length + ' items'"></span>
            </div>

            <div class="p-4 max-h-[calc(100vh-200px)] overflow-y-auto scrollbar-thin">
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                    <template x-for="p in filtered" :key="p.variant_id">
                        <button type="button" @click="add(p)" :disabled="p.available < 1"
                            class="group text-left border border-[#E9E4E0] rounded-xl overflow-hidden bg-white hover:border-wine-700 hover:shadow-md transition disabled:opacity-45 disabled:cursor-not-allowed disabled:hover:shadow-none disabled:hover:border-[#E9E4E0]">
                            <div class="aspect-[5/3] relative" :style="p.image ? '' : `background:linear-gradient(155deg, ${p.tone}, ${p.tone2})`">
                                <img x-show="p.image" :src="p.image" :alt="p.product" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
                                <span class="absolute top-2 right-2 text-[10.5px] font-semibold px-2 py-[3px] rounded-full backdrop-blur-sm"
                                    :class="p.available < 1 ? 'bg-red-600/90 text-white' : (p.available <= 5 ? 'bg-amber-500/90 text-white' : 'bg-white/85 text-gray-700')"
                                    x-text="p.available < 1 ? 'Sold out' : p.available + ' left'"></span>
                                <span class="absolute bottom-2 left-2 w-7 h-7 rounded-full bg-white/90 grid place-items-center text-wine-700 opacity-0 group-hover:opacity-100 transition shadow">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                                </span>
                            </div>
                            <div class="p-2.5">
                                <div class="text-[13px] font-semibold text-gray-900 leading-tight line-clamp-2" x-text="p.product"></div>
                                <div class="text-[11px] text-gray-400 mt-0.5 truncate" x-text="p.variant"></div>
                                <div class="text-[14px] font-semibold text-wine-700 mt-1.5" x-text="money(p.price)"></div>
                            </div>
                        </button>
                    </template>
                </div>
                <div x-show="filtered.length === 0" class="py-16 text-center text-gray-400">
                    <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <div class="text-sm">No products match “<span x-text="search"></span>”.</div>
                </div>
            </div>
        </div>

        {{-- Cart / checkout --}}
        <form method="POST" action="{{ route('admin.pos.store') }}" class="bg-white border border-[#E9E4E0] rounded-2xl flex flex-col lg:sticky lg:top-[88px]">
            @csrf
            <template x-for="item in cart" :key="item.variant_id">
                <div>
                    <input type="hidden" :name="`items[${item.variant_id}][variant_id]`" :value="item.variant_id">
                    <input type="hidden" :name="`items[${item.variant_id}][quantity]`" :value="item.quantity">
                </div>
            </template>

            <div class="px-5 py-4 border-b border-[#F0ECE9] flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="text-[15px] font-semibold">Current sale</span>
                    <span x-show="cart.length" class="text-[11px] font-semibold px-2 py-[2px] rounded-full bg-wine-700 text-white" x-text="itemCount"></span>
                </div>
                <button type="button" @click="cart = []" x-show="cart.length"
                    class="inline-flex items-center gap-1.5 text-[12px] text-red-600 hover:underline"><x-ui.icon name="trash" :size="13" />Clear all</button>
            </div>

            <div class="flex-1 max-h-[38vh] overflow-y-auto scrollbar-thin divide-y divide-[#F5F2F0]">
                <div x-show="cart.length === 0" class="px-5 py-12 text-center text-gray-400">
                    <svg class="w-9 h-9 mx-auto mb-2.5 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 14h6m-3-3v6m-7 4h14a2 2 0 002-2V8a2 2 0 00-2-2h-3.5l-1-2h-5l-1 2H5a2 2 0 00-2 2v11a2 2 0 002 2z"/></svg>
                    <div class="text-sm">Tap a product to start a sale.</div>
                </div>
                <template x-for="item in cart" :key="item.variant_id">
                    <div class="px-4 py-3 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg shrink-0 overflow-hidden bg-cover bg-center"
                            :style="item.image ? `background-image:url('${item.image}')` : `background:linear-gradient(155deg, ${item.tone}, ${item.tone2})`"></div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[13px] font-semibold truncate" x-text="item.product"></div>
                            <div class="text-[11.5px] text-gray-500 truncate" x-text="item.variant + ' · ' + money(item.price)"></div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" @click="dec(item)" class="w-7 h-7 rounded-md border border-[#E9E4E0] grid place-items-center text-gray-600 hover:bg-[#FAF9F8]">−</button>
                            <span class="w-6 text-center text-[13px] font-semibold" x-text="item.quantity"></span>
                            <button type="button" @click="inc(item)" :disabled="item.quantity >= item.available"
                                class="w-7 h-7 rounded-md border border-[#E9E4E0] grid place-items-center text-gray-600 hover:bg-[#FAF9F8] disabled:opacity-40">+</button>
                        </div>
                        <div class="w-16 text-right text-[13px] font-semibold" x-text="money(item.price * item.quantity)"></div>
                        <button type="button" @click="cart = cart.filter(i => i.variant_id !== item.variant_id)" class="text-gray-300 hover:text-red-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>
                        </button>
                    </div>
                </template>
            </div>

            <div class="px-5 py-4 border-t border-[#F0ECE9] space-y-3">
                <div class="grid grid-cols-2 gap-2.5">
                    <input name="customer_name" type="text" placeholder="Customer name (optional)"
                        class="h-9 border border-[#E9E4E0] rounded-lg px-3 text-[13px] bg-white outline-none focus:border-wine-700">
                    <input name="customer_phone" type="text" placeholder="Phone (optional)"
                        class="h-9 border border-[#E9E4E0] rounded-lg px-3 text-[13px] bg-white outline-none focus:border-wine-700">
                </div>

                <div class="rounded-xl bg-[#FAF9F8] border border-[#F0ECE9] p-3.5 space-y-2">
                    <div class="flex items-center justify-between text-[13.5px]">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold" x-text="money(subtotal)"></span>
                    </div>
                    <div class="flex items-center justify-between text-[13.5px]">
                        <span class="text-gray-600">Discount</span>
                        <div class="flex items-center gap-1">
                            <span class="text-gray-400" x-text="symbol"></span>
                            <input name="discount_amount" type="number" min="0" step="0.01" x-model.number="discount"
                                class="w-24 h-8 border border-[#E9E4E0] rounded-lg px-2 text-[13px] text-right bg-white outline-none focus:border-wine-700">
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-[16px] font-semibold pt-2 border-t border-[#E9E4E0]">
                        <span>Total due</span>
                        <span class="text-wine-700" x-text="money(total)"></span>
                    </div>
                </div>

                {{-- Quick cash --}}
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" @click="paid = total" class="px-2.5 h-8 rounded-lg border border-[#E9E4E0] text-[12px] font-semibold text-gray-700 hover:border-wine-700 hover:bg-[#FAF9F8] inline-flex items-center gap-1"><x-ui.icon name="cash" :size="13" />Exact</button>
                    <template x-for="amt in quickCash" :key="amt">
                        <button type="button" @click="paid = amt" class="px-2.5 h-8 rounded-lg border border-[#E9E4E0] text-[12px] font-semibold text-gray-700 hover:border-wine-700 hover:bg-[#FAF9F8]" x-text="money(amt)"></button>
                    </template>
                </div>

                <div class="flex items-center justify-between text-[13.5px]">
                    <span class="text-gray-600">Cash received</span>
                    <div class="flex items-center gap-1">
                        <span class="text-gray-400" x-text="symbol"></span>
                        <input name="amount_paid" type="number" min="0" step="0.01" x-model.number="paid"
                            class="w-28 h-9 border border-[#E9E4E0] rounded-lg px-2 text-[14px] font-semibold text-right bg-white outline-none focus:border-wine-700">
                    </div>
                </div>
                <div class="flex items-center justify-between text-[13.5px]">
                    <span class="text-gray-600">Change</span>
                    <span class="text-[15px] font-semibold" :class="change < 0 ? 'text-red-600' : 'text-green-700'" x-text="money(change)"></span>
                </div>

                <button type="submit" :disabled="cart.length === 0 || paid < total"
                    class="w-full h-12 rounded-xl bg-wine-700 hover:bg-[#2A2220] text-white text-[15px] font-semibold disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                    Complete sale · <span x-text="money(total)"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('head')
<script>
    function pos(catalog, symbol) {
        return {
            catalog,
            symbol,
            search: '',
            cart: [],
            discount: 0,
            paid: 0,
            get filtered() {
                const q = this.search.trim().toLowerCase();
                if (!q) return this.catalog;
                return this.catalog.filter(p =>
                    (p.product + ' ' + p.variant + ' ' + p.sku).toLowerCase().includes(q));
            },
            get subtotal() {
                return this.cart.reduce((s, i) => s + i.price * i.quantity, 0);
            },
            get total() {
                return Math.max(0, this.subtotal - (this.discount || 0));
            },
            get change() {
                return (this.paid || 0) - this.total;
            },
            get itemCount() {
                return this.cart.reduce((s, i) => s + i.quantity, 0);
            },
            get quickCash() {
                const t = this.total;
                if (t <= 0) return [];
                // Next round notes above the total.
                return [
                    Math.ceil(t / 500) * 500,
                    Math.ceil(t / 1000) * 1000,
                    Math.ceil(t / 1000) * 1000 + 1000,
                ].filter((v, i, a) => v > t && a.indexOf(v) === i).slice(0, 3);
            },
            money(v) {
                return this.symbol + Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
            },
            add(p) {
                if (p.available < 1) return;
                const existing = this.cart.find(i => i.variant_id === p.variant_id);
                if (existing) { this.inc(existing); return; }
                this.cart.push({ ...p, quantity: 1 });
            },
            inc(item) {
                if (item.quantity < item.available) item.quantity++;
            },
            dec(item) {
                item.quantity--;
                if (item.quantity < 1) this.cart = this.cart.filter(i => i.variant_id !== item.variant_id);
            },
        };
    }
</script>
@endpush
