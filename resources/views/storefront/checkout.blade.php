@extends('layouts.app')
@section('title', 'চেকআউট')
@section('no_tabbar', true)
@section('no_footer', true)
@section('hide_header_search', true)

@php
    $insideCity = config('shop.inside_city');
    $methods = collect([
        ['value' => 'cod', 'label' => 'ক্যাশ অন ডেলিভারি', 'hint' => 'পণ্য হাতে পেয়ে টাকা দিন', 'tag' => 'জনপ্রিয়', 'on' => (bool) $general['cod_enabled'],
            'icon' => '<rect x="2.5" y="6" width="19" height="12" rx="2.5"/><circle cx="12" cy="12" r="2.6"/><path d="M6 9.5v.01M18 14.5v.01"/>'],
        ['value' => 'mobile_banking', 'label' => 'বিকাশ / নগদ', 'hint' => 'মোবাইল ব্যাংকিংয়ে পেমেন্ট', 'tag' => null, 'on' => (bool) $general['mobile_banking_enabled'],
            'icon' => '<rect x="6.5" y="2.5" width="11" height="19" rx="2.5"/><path d="M10.5 18h3"/>'],
        ['value' => 'card', 'label' => 'কার্ড', 'hint' => 'ভিসা, মাস্টারকার্ড', 'tag' => null, 'on' => (bool) $general['card_enabled'],
            'icon' => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19M6.5 15h4"/>'],
    ])->where('on', true)->values();
    $selectedMethod = old('payment_method', $methods->first()['value'] ?? 'cod');
    $itemCount = $items->sum('quantity');
    $lockIcon = '<path d="M8 11V8a4 4 0 0 1 8 0v3"/><rect x="5" y="11" width="14" height="10" rx="2"/>';
@endphp

@section('mobile_header')
    <div class="sm:hidden sticky top-0 z-40 bg-accent-soft/95 backdrop-blur px-4 pt-3 pb-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('store.cart') }}" class="w-9 h-9 rounded-full bg-white grid place-items-center text-[16px] text-accent" aria-label="ফিরে যান">←</a>
            <div class="text-[20px] font-semibold">চেকআউট</div>
            <span class="ml-auto inline-flex items-center gap-1 text-[12px] font-medium text-accent">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">{!! $lockIcon !!}</svg>
                নিরাপদ চেকআউট
            </span>
        </div>
        <div class="mt-3 flex gap-1.5 items-center">
            <template x-for="i in 3" :key="i">
                <span class="flex-1 h-1.5 rounded-full transition-colors duration-300" :class="i <= $store.checkout.step ? 'bg-accent' : 'bg-white'"></span>
            </template>
        </div>
        <div class="mt-2 text-[13px] font-medium text-accent">ধাপ <span x-text="bnNumber($store.checkout.step)"></span> / ৩ · <span x-text="$store.checkout.stepLabel"></span></div>
    </div>
@endsection

@section('content')
<div class="nf-checkout px-4 sm:px-8 pt-4 sm:pt-10 pb-[120px] sm:pb-16 nf-fade">

    <div class="hidden sm:flex items-end justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-[34px] font-semibold">চেকআউট</h1>
            <div class="mt-1 inline-flex items-center gap-1.5 text-[14px] text-accent">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">{!! $lockIcon !!}</svg>
                নিরাপদ চেকআউট · ক্যাশ অন ডেলিভারি সারাদেশে
            </div>
        </div>
        <div class="flex gap-2 items-center flex-wrap text-[14px] font-medium">
            @foreach(['তথ্য', 'ঠিকানা', 'পেমেন্ট'] as $i => $label)
                @unless($loop->first)<span class="w-6 h-px bg-sky"></span>@endunless
                <span class="rounded-full px-4 py-2 transition-colors duration-300" :class="$store.checkout.step >= {{ $i + 1 }} ? 'bg-accent text-white' : 'bg-white text-muted'">{{ bn_digits($i + 1) }} · {{ $label }}</span>
            @endforeach
        </div>
    </div>

    @if($errors->any())
        <div class="mt-4 sm:mt-6 bg-rose-soft text-rose rounded-2xl px-5 py-4 text-[14.5px]">
            <ul class="list-disc pl-4 flex flex-col gap-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('store.checkout.store') }}" class="mt-4 sm:mt-6 grid desk:grid-cols-[1fr_390px] gap-4 sm:gap-6 desk:gap-8 items-start">
        @csrf
        <div class="flex flex-col gap-4 sm:gap-6">
            {{-- Customer --}}
            <section class="nf-co-card">
                <h2 class="nf-co-head"><span class="nf-co-step">১</span>আপনার তথ্য</h2>
                <div class="mt-4 grid sm:grid-cols-2 gap-3.5 sm:gap-4">
                    <div>
                        <label class="nf-label" for="co-name">পুরো নাম</label>
                        <input id="co-name" name="name" x-model="$store.checkout.name" required maxlength="100" autocomplete="name" class="nf-input" placeholder="আপনার নাম">
                    </div>
                    <div>
                        <label class="nf-label" for="co-phone">মোবাইল নম্বর</label>
                        <input id="co-phone" name="phone" type="tel" x-model="$store.checkout.phone" required inputmode="tel" autocomplete="tel" class="nf-input" placeholder="০১৭১২ ৩৪৫ ৬৭৮">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="nf-label" for="co-email">ইমেইল <span class="font-normal opacity-70">(ঐচ্ছিক)</span></label>
                        <input id="co-email" name="email" type="email" value="{{ old('email', $prefill['email']) }}" autocomplete="email" class="nf-input" placeholder="you@email.com">
                    </div>
                </div>
            </section>

            {{-- Address --}}
            <section class="nf-co-card">
                <h2 class="nf-co-head"><span class="nf-co-step">২</span>ডেলিভারি ঠিকানা</h2>
                <div class="mt-4 flex flex-col gap-3.5 sm:gap-4">
                    <div>
                        <label class="nf-label" for="co-city">জেলা</label>
                        <select id="co-city" name="city" x-model="$store.checkout.city" data-search class="nf-input">
                            @foreach(config('shop.districts') as $division => $districts)
                                <optgroup label="{{ $division }}">
                                    @foreach($districts as $district => $englishName)
                                        <option value="{{ $district }}" data-search="{{ $englishName }}">{{ $district }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="nf-label" for="co-address">সম্পূর্ণ ঠিকানা</label>
                        <textarea id="co-address" name="address" x-model="$store.checkout.address" required maxlength="255" rows="3" autocomplete="street-address"
                                  class="nf-input resize-none leading-[1.7]" placeholder="বাড়ি/ফ্ল্যাট নম্বর, রোড, এলাকা, থানা/উপজেলা"></textarea>
                        <div class="mt-1.5 text-[12.5px] text-muted">রাইডার যেন সহজে খুঁজে পান, কাছের কোনো পরিচিত জায়গাও লিখে দিন।</div>
                    </div>

                    {{-- Delivery charge follows the district --}}
                    <div class="rounded-2xl bg-accent-soft p-3.5 sm:p-4 flex items-center gap-3">
                        <span class="w-10 h-10 flex-none rounded-full bg-white text-accent grid place-items-center">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6.5h11v9H3zM14 9.5h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17.5" cy="17.5" r="1.8"/></svg>
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="text-[14.5px] font-semibold" x-text="$store.checkout.zone === 'inside' ? 'ঢাকার ভেতরে · ২৪ ঘণ্টার মধ্যে' : 'ঢাকার বাইরে · ২–৩ কর্মদিবস'"></div>
                            <div class="text-[12.5px] text-accent">
                                জেলা অনুযায়ী ডেলিভারি চার্জ@if($general['free_delivery_threshold'] > 0) · {{ bn_price($general['free_delivery_threshold']) }}+ অর্ডারে ফ্রি@endif
                            </div>
                        </div>
                        <div class="text-[16px] font-semibold text-accent" x-text="$store.checkout.delivery > 0 ? '৳' + bnNumber($store.checkout.delivery) : 'ফ্রি'"></div>
                    </div>
                </div>
            </section>

            {{-- Payment --}}
            <section class="nf-co-card">
                <h2 class="nf-co-head"><span class="nf-co-step">৩</span>পেমেন্ট পদ্ধতি</h2>
                <div class="mt-4 grid gap-3" role="radiogroup" aria-label="পেমেন্ট পদ্ধতি">
                    @foreach($methods as $option)
                        <label class="nf-pay" :class="$store.checkout.method === @js($option['value']) && 'is-active'">
                            <input type="radio" name="payment_method" value="{{ $option['value'] }}" x-model="$store.checkout.method" class="sr-only">
                            <span class="nf-pay-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $option['icon'] !!}</svg>
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="flex items-center gap-2 flex-wrap">
                                    <span class="text-[15.5px] font-semibold text-ink">{{ $option['label'] }}</span>
                                    @if($option['tag'])<span class="rounded-full bg-moss-soft text-moss px-2 py-0.5 text-[11px] font-semibold">{{ $option['tag'] }}</span>@endif
                                </span>
                                <span class="block mt-0.5 text-[13px] text-muted">{{ $option['hint'] }}</span>
                            </span>
                            <span class="nf-pay-radio" aria-hidden="true"></span>
                        </label>
                    @endforeach
                    <div x-show="$store.checkout.method !== 'cod'" x-collapse x-cloak>
                        <div class="rounded-xl bg-accent-soft px-4 py-3 text-[13px] text-accent">অর্ডার নিশ্চিত করার পর আমাদের টিম পেমেন্টের নির্দেশনা জানিয়ে দেবে।</div>
                    </div>
                </div>

                <div class="mt-5">
                    <label class="nf-label" for="co-notes">অর্ডার নোট <span class="font-normal opacity-70">(ঐচ্ছিক)</span></label>
                    <textarea id="co-notes" name="notes" rows="2" maxlength="500" class="nf-input resize-none" placeholder="যেমন: বিকেল ৫টার পরে ডেলিভারি চাই">{{ old('notes') }}</textarea>
                </div>
            </section>
        </div>

        {{-- Summary --}}
        <aside class="nf-co-card desk:sticky desk:top-28">
            <div class="flex items-baseline justify-between">
                <h2 class="text-[17px] sm:text-[18px] font-semibold">অর্ডার সারাংশ</h2>
                <span class="text-[13px] text-muted">{{ bn_digits($itemCount) }}টি পণ্য</span>
            </div>
            <div class="mt-4 flex flex-col gap-3.5">
                @foreach($items as $line)
                    @php $lineVariant = $line['variant']; @endphp
                    <div class="flex gap-3 items-center">
                        <x-ui.product-image :product="$lineVariant->product" :variant="$lineVariant" class="w-[52px] h-[52px] rounded-[12px] flex-none" />
                        <div class="flex-1 min-w-0">
                            <div class="text-[15px] font-semibold truncate">{{ $lineVariant->product->name }}</div>
                            <div class="text-[13px] text-muted">{{ bn_digits($lineVariant->shortLabel()) }} · {{ bn_digits($line['quantity']) }}টি</div>
                        </div>
                        <div class="text-[15px] font-semibold">{{ bn_price($line['line_total']) }}</div>
                    </div>
                @endforeach
            </div>
            <div class="my-4 border-t border-dashed border-sky"></div>
            <div class="flex flex-col gap-2.5 text-[14.5px] text-cocoa">
                <div class="flex justify-between"><span>সাবটোটাল</span><span class="text-ink">{{ bn_price($summary['subtotal']) }}</span></div>
                @if($summary['discount'] > 0)
                    <div class="flex justify-between"><span>ছাড় ({{ $summary['coupon'] }})</span><span class="text-moss font-medium">−{{ bn_price($summary['discount']) }}</span></div>
                @endif
                <div class="flex justify-between"><span>ডেলিভারি</span><span class="text-ink" x-text="$store.checkout.delivery > 0 ? '৳' + bnNumber($store.checkout.delivery) : 'ফ্রি'"></span></div>
            </div>
            <div class="mt-4 rounded-2xl bg-accent-soft px-4 py-3.5 flex justify-between items-center">
                <span class="text-[16px] font-semibold">সর্বমোট</span>
                <span class="text-[24px] font-semibold text-accent" x-text="'৳' + bnNumber($store.checkout.total)"></span>
            </div>
            <button class="hidden sm:block mt-5 w-full bg-accent text-white rounded-full py-[17px] text-[15.5px] font-semibold shadow-[0_14px_30px_-16px_rgba(60,90,120,.9)] transition hover:brightness-110 active:scale-[.99]">অর্ডার নিশ্চিত করুন</button>
            <div class="mt-3 text-[12.5px] leading-[1.7] text-muted">
                অর্ডার করলে আপনি আমাদের <a href="{{ route('store.page', 'terms') }}" class="underline">শর্তাবলি</a> ও
                <a href="{{ route('store.page', 'privacy') }}" class="underline">প্রাইভেসি পলিসিতে</a> সম্মত হচ্ছেন।
            </div>
        </aside>

        {{-- Sticky confirm bar (phones) --}}
        <div class="sm:hidden fixed inset-x-0 bottom-0 z-50 bg-white px-4 pt-3 pb-[max(12px,env(safe-area-inset-bottom))] flex gap-3 items-center shadow-[0_-10px_24px_-18px_rgba(60,90,120,.8)]">
            <div>
                <div class="text-[18px] font-semibold text-accent" x-text="'৳' + bnNumber($store.checkout.total)"></div>
                <div class="text-[12px] text-muted" x-text="$store.checkout.method === 'cod' ? 'ক্যাশ অন ডেলিভারি' : 'অনলাইন পেমেন্ট'"></div>
            </div>
            <button class="flex-1 bg-accent text-white rounded-full py-[15px] text-[15px] font-semibold shadow-[0_12px_26px_-14px_rgba(60,90,120,.9)] transition active:scale-[.98]">অর্ডার নিশ্চিত করুন</button>
        </div>
    </form>
</div>
@endsection

@push('head')
<style>
    /* Soft blue wash behind the whole checkout page. */
    body { background: linear-gradient(180deg, var(--color-accent-soft) 0, #fff 900px) no-repeat; }
</style>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('checkout', {
            city: @json(old('city', $prefill['city'])),
            name: @json(old('name', $prefill['name'])),
            phone: @json(old('phone', $prefill['phone'])),
            address: @json(old('address', $prefill['address'])),
            method: @json($selectedMethod),
            subtotal: {{ $summary['subtotal'] }},
            discount: {{ $summary['discount'] }},
            rates: { inside: {{ (float) $general['delivery_inside'] }}, outside: {{ (float) $general['delivery_outside'] }}, free: {{ (float) $general['free_delivery_threshold'] }} },
            get zone() { return this.city === @json($insideCity) ? 'inside' : 'outside'; },
            get delivery() { return this.rates.free > 0 && this.subtotal >= this.rates.free ? 0 : this.rates[this.zone]; },
            get total() { return Math.max(0, this.subtotal - this.discount + this.delivery); },
            get step() {
                if (! (this.name && this.phone)) return 1;
                if (! this.address) return 2;
                return 3;
            },
            get stepLabel() { return ['তথ্য', 'ঠিকানা', 'পেমেন্ট'][this.step - 1]; },
        });
    });
</script>
@endpush
