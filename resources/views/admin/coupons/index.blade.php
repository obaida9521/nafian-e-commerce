@extends('layouts.admin')
@section('title', 'কুপন ও অফার')

@php $symbol = config('shop.currency_symbol'); @endphp

@section('content')
<div x-data="{
        open: {{ $errors->any() ? 'true' : 'false' }},
        editing: false,
        action: @js(route('admin.coupons.store')),
        blank: { code: '', description: '', type: 'percentage', value: '', min_order_amount: '', max_discount_amount: '', max_uses: '', valid_from: '', valid_until: '', is_active: true },
        form: {
            code: @js(old('code', '')), description: @js(old('description', '')), type: @js(old('type', 'percentage')),
            value: @js(old('value', '')), min_order_amount: @js(old('min_order_amount', '')), max_discount_amount: @js(old('max_discount_amount', '')),
            max_uses: @js(old('max_uses', '')), valid_from: @js(old('valid_from', '')), valid_until: @js(old('valid_until', '')), is_active: true,
        },
        create() { this.editing = false; this.action = @js(route('admin.coupons.store')); this.form = { ...this.blank }; this.open = true; },
        edit(coupon, url) { this.editing = true; this.action = url; this.form = { ...this.blank, ...coupon }; this.open = true; },
     }">

    <div class="flex justify-between items-end gap-3.5 flex-wrap">
        <div>
            <h1 class="font-display text-[28px]">কুপন ও অফার</h1>
            <div class="mt-1 text-[14px] text-muted">{{ bn_digits($coupons->total()) }}টি কুপন</div>
        </div>
        @adminCan('coupons')
            <button @click="create()" class="inline-flex items-center gap-1.5 bg-mocha text-white rounded-full px-5 py-2.5 text-[14px] font-medium"><x-ui.icon name="plus" :size="16" />নতুন কুপন</button>
        @endadminCan
    </div>

    <div class="mt-4 grid gap-[18px] desk:grid-cols-3">
        @forelse($coupons as $coupon)
            @php
                $expired = $coupon->valid_until && $coupon->valid_until->isPast();
                $maxed = $coupon->max_uses && $coupon->used_count >= $coupon->max_uses;
                $state = ! $coupon->is_active ? ['বন্ধ', '#F3EFEC', '#3E3532']
                    : (($expired || $maxed) ? ['মেয়াদোত্তীর্ণ', '#F3EFEC', '#3E3532'] : ['চালু', '#F1F4F1', '#3F5A42']);
                $usage = $coupon->max_uses ? min(100, round($coupon->used_count / $coupon->max_uses * 100)) : min(100, $coupon->used_count);
                $payload = [
                    'code' => $coupon->code, 'description' => $coupon->description, 'type' => $coupon->type,
                    'value' => (float) $coupon->value, 'min_order_amount' => (float) $coupon->min_order_amount,
                    'max_discount_amount' => $coupon->max_discount_amount !== null ? (float) $coupon->max_discount_amount : '',
                    'max_uses' => $coupon->max_uses, 'valid_from' => $coupon->valid_from?->format('Y-m-d'),
                    'valid_until' => $coupon->valid_until?->format('Y-m-d'), 'is_active' => (bool) $coupon->is_active,
                ];
            @endphp
            <x-admin.card>
                <div class="flex justify-between items-center gap-2">
                    <div @class(['text-[17px] font-semibold tracking-[0.04em]', 'text-muted' => $expired || $maxed || ! $coupon->is_active])>{{ $coupon->code }}</div>
                    <span class="rounded-full px-3 py-1.5 text-[12.5px] font-semibold" style="background:{{ $state[1] }};color:{{ $state[2] }};">{{ $state[0] }}</span>
                </div>

                <div class="mt-2 text-[14.5px] leading-[1.8] text-cocoa">
                    {{ $coupon->summaryBn() }}<br>
                    @if($coupon->min_order_amount > 0)ন্যূনতম {{ bn_price($coupon->min_order_amount) }} অর্ডারে@else সব অর্ডারে প্রযোজ্য @endif
                    @if($coupon->description)<br><span class="text-muted">{{ $coupon->description }}</span>@endif
                </div>

                <div class="nf-line my-[18px]"></div>
                <div class="flex justify-between text-[14px] text-muted">
                    <span>ব্যবহার</span>
                    <span class="text-ink">{{ bn_digits($coupon->used_count) }} / {{ $coupon->max_uses ? bn_digits($coupon->max_uses) : 'সীমাহীন' }}</span>
                </div>
                <div class="mt-2 h-2 rounded-full bg-hair">
                    <div class="h-2 rounded-full" style="width:{{ $usage }}%;background:{{ $expired || $maxed ? '#C9BFB9' : '#2A2220' }};"></div>
                </div>
                <div class="mt-2.5 text-[13.5px] text-muted">
                    @if($coupon->valid_until)
                        {{ $expired ? 'শেষ হয়েছে' : 'মেয়াদ' }} {{ bn_date($coupon->valid_until) }}
                    @else
                        মেয়াদ নেই
                    @endif
                </div>

                @adminCan('coupons')
                    <div class="mt-4 flex gap-3.5 text-[14px] font-semibold">
                        <button @click="edit(@js($payload), @js(route('admin.coupons.update', $coupon)))" class="inline-flex items-center gap-1.5 text-accent"><x-ui.icon name="edit" :size="14" />সম্পাদনা</button>
                        <x-admin.confirm-modal :action="route('admin.coupons.destroy', $coupon)" title="কুপন মুছবেন?"
                                               :message="$coupon->code.' কুপনটি মুছে ফেলা হবে।'" trigger="মুছুন" class="text-rose cursor-pointer" />
                    </div>
                @endadminCan
            </x-admin.card>
        @empty
            <div class="desk:col-span-3 bg-white rounded-[20px] py-16 text-center text-[15px] text-muted nf-shadow-soft">এখনো কোনো কুপন নেই।</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $coupons->links() }}</div>

    {{-- Create / edit dialog --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-[80] grid place-items-center px-5">
        <div @click="open = false" class="absolute inset-0 bg-[#1A1413]/45"></div>
        <form method="POST" :action="action" class="relative bg-white rounded-[22px] w-[560px] max-w-full p-6 max-h-[90vh] overflow-y-auto">
            @csrf
            <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>
            <div class="text-[18px] font-semibold" x-text="editing ? 'কুপন সম্পাদনা' : 'নতুন কুপন'"></div>

            <div class="mt-4 grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="nf-label" for="c-code">কোড</label>
                    <input id="c-code" name="code" x-model="form.code" required placeholder="NAFIAN15" class="nf-input nf-input-soft uppercase">
                </div>
                <div>
                    <label class="nf-label" for="c-type">ধরন</label>
                    <select id="c-type" name="type" x-model="form.type" class="nf-input nf-input-soft">
                        <option value="percentage">শতকরা ছাড়</option>
                        <option value="fixed">নির্দিষ্ট টাকা</option>
                        <option value="second_item_percentage">২য় পণ্যে শতকরা ছাড়</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="nf-label" for="c-desc">বিবরণ <span class="text-muted">(ঐচ্ছিক)</span></label>
                    <input id="c-desc" name="description" x-model="form.description" maxlength="150" placeholder="সব আতরে প্রযোজ্য" class="nf-input nf-input-soft">
                </div>
                <div>
                    <label class="nf-label" for="c-value">মান <span class="text-muted" x-text="form.type === 'fixed' ? '({{ $symbol }})' : '(%)'"></span></label>
                    <input id="c-value" name="value" x-model="form.value" type="number" step="0.01" required placeholder="15" class="nf-input nf-input-soft">
                </div>
                <div>
                    <label class="nf-label" for="c-cap">সর্বোচ্চ ছাড় <span class="text-muted">(ঐচ্ছিক)</span></label>
                    <input id="c-cap" name="max_discount_amount" x-model="form.max_discount_amount" type="number" step="0.01" placeholder="500" class="nf-input nf-input-soft">
                </div>
                <div>
                    <label class="nf-label" for="c-min">ন্যূনতম অর্ডার</label>
                    <input id="c-min" name="min_order_amount" x-model="form.min_order_amount" type="number" step="0.01" placeholder="0" class="nf-input nf-input-soft">
                </div>
                <div>
                    <label class="nf-label" for="c-max-uses">সর্বোচ্চ ব্যবহার</label>
                    <input id="c-max-uses" name="max_uses" x-model="form.max_uses" type="number" placeholder="সীমাহীন" class="nf-input nf-input-soft">
                </div>
                <div>
                    <label class="nf-label" for="c-from">শুরুর তারিখ</label>
                    <input id="c-from" name="valid_from" x-model="form.valid_from" type="date" class="nf-input nf-input-soft">
                </div>
                <div>
                    <label class="nf-label" for="c-until">শেষ তারিখ</label>
                    <input id="c-until" name="valid_until" x-model="form.valid_until" type="date" class="nf-input nf-input-soft">
                </div>
                <label class="sm:col-span-2 flex justify-between items-center gap-3 cursor-pointer text-[14.5px]">
                    <span>কুপন চালু</span>
                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="peer sr-only">
                    <span class="nf-switch"><span></span></span>
                </label>
            </div>

            <div class="mt-5 flex justify-end gap-2.5">
                <button type="button" @click="open = false" class="inline-flex items-center gap-1.5 rounded-full px-6 py-3 bg-hair text-[14.5px] font-semibold"><x-ui.icon name="x" :size="15" />বাতিল</button>
                <button class="inline-flex items-center gap-1.5 rounded-full px-6 py-3 bg-mocha text-white text-[14.5px] font-semibold"><x-ui.icon name="check" :size="15" /><span x-text="editing ? 'সংরক্ষণ করুন' : 'কুপন তৈরি করুন'"></span></button>
            </div>
        </form>
    </div>
</div>
@endsection
