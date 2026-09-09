@extends('layouts.admin')
@section('title', 'Coupons')

@php $symbol = config('shop.currency_symbol'); @endphp

@section('content')
<div class="max-w-[1280px] mx-auto"
     x-data="{
        open: {{ $errors->any() ? 'true' : 'false' }}, editing: false, action: '{{ route('admin.coupons.store') }}',
        form: { code:@js(old('code','')), type:@js(old('type','percentage')), value:@js(old('value','')), min_order_amount:@js(old('min_order_amount','')), max_uses:@js(old('max_uses','')), valid_from:@js(old('valid_from','')), valid_until:@js(old('valid_until','')), is_active:true },
        create() { this.editing=false; this.action='{{ route('admin.coupons.store') }}'; this.form={ code:'', type:'percentage', value:'', min_order_amount:'', max_uses:'', valid_from:'', valid_until:'', is_active:true }; this.open=true; },
        edit(c, url) { this.editing=true; this.action=url; this.form=Object.assign({ is_active:true }, c); this.open=true; }
     }">
    @adminCan('coupons')
    <div class="flex justify-end mb-4.5">
        <button @click="create()" class="h-10 px-4.5 rounded-lg bg-wine-700 hover:bg-[#4d141e] text-white text-sm font-semibold flex items-center gap-2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>Add coupon
        </button>
    </div>
    @endadminCan

    <div class="bg-white border border-[#EADBC4] rounded-xl overflow-hidden">
        <div class="grid grid-cols-[1.2fr_1.2fr_0.8fr_1fr_1.6fr_0.9fr_110px] px-5 py-3 text-[11px] tracking-wide uppercase text-gray-400 font-semibold border-b border-[#EFE2CE]">
            <span>Code</span><span>Type</span><span>Value</span><span>Used</span><span>Valid</span><span>Status</span><span class="text-right">Actions</span>
        </div>
        @forelse($coupons as $coupon)
            @php
                $expired = $coupon->valid_until && $coupon->valid_until->isPast();
                $maxed = $coupon->max_uses && $coupon->used_count >= $coupon->max_uses;
                $status = (! $coupon->is_active) ? ['Inactive', '#F3F4F6', '#374151'] : (($expired || $maxed) ? ['Expired', '#F3F4F6', '#374151'] : ['Active', '#DCFCE7', '#166534']);
                $payload = ['code' => $coupon->code, 'type' => $coupon->type, 'value' => (float) $coupon->value, 'min_order_amount' => (float) $coupon->min_order_amount, 'max_uses' => $coupon->max_uses, 'valid_from' => $coupon->valid_from?->format('Y-m-d'), 'valid_until' => $coupon->valid_until?->format('Y-m-d'), 'is_active' => (bool) $coupon->is_active];
            @endphp
            <div class="grid grid-cols-[1.2fr_1.2fr_0.8fr_1fr_1.6fr_0.9fr_110px] px-5 py-3.5 items-center border-b border-[#F6EAD8] text-[13.5px] hover:bg-[#FBEFDD]">
                <span class="font-mono text-[13px] font-medium">{{ $coupon->code }}</span>
                <span class="text-gray-500 capitalize">{{ $coupon->type }}</span>
                <span class="font-semibold">{{ $coupon->isPercentage() ? rtrim(rtrim(number_format($coupon->value, 2), '0'), '.').'%' : $symbol.number_format($coupon->value, 0) }}</span>
                <span class="text-gray-500">{{ $coupon->used_count }}{{ $coupon->max_uses ? ' / '.$coupon->max_uses : '' }}</span>
                <span class="text-gray-500 text-xs">{{ $coupon->valid_from?->format('M j, Y') ?? '—' }} – {{ $coupon->valid_until?->format('M j, Y') ?? '∞' }}</span>
                <span><span class="text-[11px] font-semibold px-2.5 py-[3px] rounded-full" style="background:{{ $status[1] }};color:{{ $status[2] }};">{{ $status[0] }}</span></span>
                <span class="flex justify-end gap-3.5">
                    <button @click="edit(@js($payload), @js(route('admin.coupons.update', $coupon)))" class="text-wine-700 font-semibold">Edit</button>
                    <x-admin.confirm-modal :action="route('admin.coupons.destroy', $coupon)" title="Delete coupon?" :message="'Delete '.$coupon->code.'?'" trigger="Delete" class="text-red-600 font-semibold cursor-pointer" />
                </span>
            </div>
        @empty
            <div class="px-5 py-12 text-center text-gray-400">No coupons yet.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $coupons->links() }}</div>

    {{-- Create / Edit modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-[75] flex items-center justify-center">
        <div @click="open=false" class="absolute inset-0 bg-[#3c1018]/40"></div>
        <form method="POST" :action="action" class="relative bg-white rounded-[14px] w-[520px] max-w-[92vw] p-6.5 shadow-2xl">
            @csrf
            <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>
            <div class="text-lg font-semibold mb-5" x-text="editing ? 'Edit coupon' : 'New coupon'"></div>
            <div class="grid grid-cols-2 gap-3.5 mb-5.5">
                <div><label class="block text-[13px] text-gray-500 mb-1.5">Code</label><input name="code" x-model="form.code" placeholder="AUTUMN15" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 font-mono text-[13px] uppercase outline-none focus:border-wine-700"></div>
                <div><label class="block text-[13px] text-gray-500 mb-1.5">Type</label>
                    <select name="type" x-model="form.type" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3 text-sm bg-white outline-none focus:border-wine-700">
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed</option>
                    </select>
                </div>
                <div><label class="block text-[13px] text-gray-500 mb-1.5">Value <span class="text-gray-300" x-text="form.type==='percentage' ? '(%)' : '({{ $symbol }})'"></span></label><input name="value" x-model="form.value" type="number" step="0.01" placeholder="15" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-wine-700"></div>
                <div><label class="block text-[13px] text-gray-500 mb-1.5">Min order <span class="text-gray-300">(optional)</span></label><input name="min_order_amount" x-model="form.min_order_amount" type="number" step="0.01" placeholder="0" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-wine-700"></div>
                <div><label class="block text-[13px] text-gray-500 mb-1.5">Max uses</label><input name="max_uses" x-model="form.max_uses" type="number" placeholder="500" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-wine-700"></div>
                <label class="flex items-end gap-2 text-sm text-gray-700 pb-2.5"><input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="accent-wine-700"> Active</label>
                <div><label class="block text-[13px] text-gray-500 mb-1.5">Start date</label><input name="valid_from" x-model="form.valid_from" type="date" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-wine-700"></div>
                <div><label class="block text-[13px] text-gray-500 mb-1.5">End date</label><input name="valid_until" x-model="form.valid_until" type="date" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-wine-700"></div>
            </div>
            <div class="flex justify-end gap-2.5">
                <button type="button" @click="open=false" class="h-[42px] px-5 border border-[#EADBC4] rounded-lg bg-white text-gray-700 text-sm font-semibold">Cancel</button>
                <button class="h-[42px] px-5 rounded-lg bg-wine-700 hover:bg-[#4d141e] text-white text-sm font-semibold" x-text="editing ? 'Save changes' : 'Create coupon'"></button>
            </div>
        </form>
    </div>
</div>
@endsection
