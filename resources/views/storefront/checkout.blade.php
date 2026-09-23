@extends('layouts.app')
@section('title', 'চেকআউট')
@section('no_tabbar', true)
@section('no_footer', true)
@section('hide_header_search', true)

@php
    $insideCity = config('shop.inside_city');
    $methods = collect([
        ['value' => 'cod', 'label' => 'ক্যাশ অন ডেলিভারি', 'hint' => 'পণ্য হাতে পেয়ে টাকা দিন', 'on' => (bool) $general['cod_enabled']],
        ['value' => 'mobile_banking', 'label' => 'বিকাশ / নগদ', 'hint' => 'মোবাইল ব্যাংকিং', 'on' => (bool) $general['mobile_banking_enabled']],
        ['value' => 'card', 'label' => 'কার্ড', 'hint' => 'ভিসা, মাস্টারকার্ড', 'on' => (bool) $general['card_enabled']],
    ])->where('on', true)->values();
    $selectedMethod = old('payment_method', $methods->first()['value'] ?? 'cod');
@endphp

@section('mobile_header')
    <div class="sm:hidden sticky top-0 z-40 bg-white px-5 pt-3 pb-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('store.cart') }}" class="text-[17px] font-medium" aria-label="ফিরে যান">←</a>
            <div class="text-[20px] font-semibold">চেকআউট</div>
        </div>
        <div class="mt-3 flex gap-1.5 items-center">
            <template x-for="i in 3" :key="i">
                <span class="flex-1 h-1 rounded" :class="i <= $store.checkout.step ? 'bg-espresso' : 'bg-[#E4DED9]'"></span>
            </template>
        </div>
        <div class="mt-2 text-[13px] text-muted">ধাপ <span x-text="bnNumber($store.checkout.step)"></span> / ৩ · <span x-text="$store.checkout.stepLabel"></span></div>
    </div>
@endsection

@section('content')
<div class="px-5 sm:px-8 pt-4 sm:pt-10 pb-[120px] sm:pb-16 nf-fade">

    <div class="hidden sm:block">
        <h1 class="font-display text-[38px]">চেকআউট</h1>
        <div class="mt-3.5 flex gap-2.5 items-center flex-wrap text-[14px] font-medium">
            @foreach(['তথ্য', 'ডেলিভারি', 'পেমেন্ট'] as $i => $label)
                @unless($loop->first)<span class="text-dust">—</span>@endunless
                <span class="rounded-full px-[18px] py-2" :class="$store.checkout.step >= {{ $i + 1 }} ? 'bg-espresso text-white' : 'bg-sand text-muted'">{{ bn_digits($i + 1) }} · {{ $label }}</span>
            @endforeach
        </div>
    </div>

    @if($errors->any())
        <div class="mt-5 bg-rose-soft text-rose rounded-2xl px-5 py-4 text-[14.5px]">
            <ul class="list-disc pl-4 flex flex-col gap-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('store.checkout.store') }}" class="mt-5 sm:mt-6 grid desk:grid-cols-[1fr_380px] gap-6 desk:gap-8 items-start">
        @csrf
        <div class="flex flex-col gap-5 sm:gap-6">
            {{-- Customer --}}
            <div class="sm:bg-panel sm:rounded-[22px] sm:p-[26px]">
                <div class="text-[16.5px] sm:text-[18px] font-semibold">গ্রাহকের তথ্য</div>
                <div class="mt-3 sm:mt-[18px] flex flex-col gap-3 sm:gap-4">
                    <div class="grid sm:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="nf-label" for="co-name">পুরো নাম</label>
                            <input id="co-name" name="name" x-model="$store.checkout.name" required maxlength="100" class="nf-input nf-input-soft sm:bg-white" placeholder="নুসরাত জাহান">
                        </div>
                        <div>
                            <label class="nf-label" for="co-phone">মোবাইল নম্বর</label>
                            <input id="co-phone" name="phone" x-model="$store.checkout.phone" required inputmode="tel" class="nf-input nf-input-soft sm:bg-white" placeholder="০১৭১২ ৩৪৫ ৬৭৮">
                        </div>
                    </div>
                    <div>
                        <label class="nf-label" for="co-email">ইমেইল <span class="text-muted">(ঐচ্ছিক)</span></label>
                        <input id="co-email" name="email" type="email" value="{{ old('email', $prefill['email']) }}" class="nf-input nf-input-soft sm:bg-white" placeholder="you@email.com">
                    </div>
                    <div>
                        <label class="nf-label" for="co-address">সম্পূর্ণ ঠিকানা</label>
                        <input id="co-address" name="address" x-model="$store.checkout.address" required maxlength="255" class="nf-input nf-input-soft sm:bg-white" placeholder="বাড়ি ৪২, রোড ১১, ব্লক সি">
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
                        <div>
                            <label class="nf-label" for="co-city">শহর</label>
                            <select id="co-city" name="city" x-model="$store.checkout.city" class="nf-input nf-input-soft sm:bg-white">
                                @foreach(config('shop.cities') as $cityOption)
                                    <option value="{{ $cityOption }}">{{ $cityOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="nf-label" for="co-area">এলাকা</label>
                            <input id="co-area" name="area" x-model="$store.checkout.area" required maxlength="100" list="dhaka-areas" class="nf-input nf-input-soft sm:bg-white" placeholder="বনানী">
                            <datalist id="dhaka-areas">
                                @foreach(config('shop.dhaka_areas') as $areaOption)<option value="{{ $areaOption }}"></option>@endforeach
                            </datalist>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="nf-label" for="co-postcode">পোস্ট কোড <span class="text-muted">(ঐচ্ছিক)</span></label>
                            <input id="co-postcode" name="postcode" value="{{ old('postcode', $prefill['postcode']) }}" inputmode="numeric" class="nf-input nf-input-soft sm:bg-white" placeholder="১২১৩">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Delivery --}}
            <div class="sm:bg-panel sm:rounded-[22px] sm:p-[26px]">
                <div class="text-[16.5px] sm:text-[18px] font-semibold">ডেলিভারি পদ্ধতি</div>
                <div class="mt-3 sm:mt-4 flex flex-col gap-3">
                    <div class="bg-white rounded-[16px] p-[18px] flex justify-between items-center gap-3 border border-transparent"
                         :class="$store.checkout.zone === 'inside' ? 'shadow-[inset_0_0_0_2px_#3B2F2D]' : 'opacity-60'">
                        <div>
                            <div class="text-[15.5px] font-semibold">স্ট্যান্ডার্ড · ঢাকার ভেতরে</div>
                            <div class="mt-0.5 text-[14px] text-muted">২৪ ঘণ্টার মধ্যে</div>
                        </div>
                        <div class="text-[16px] font-semibold text-espresso">{{ bn_price($general['delivery_inside']) }}</div>
                    </div>
                    <div class="bg-white rounded-[16px] p-[18px] flex justify-between items-center gap-3"
                         :class="$store.checkout.zone === 'outside' ? 'shadow-[inset_0_0_0_2px_#3B2F2D]' : 'opacity-60'">
                        <div>
                            <div class="text-[15.5px] font-semibold">ঢাকার বাইরে</div>
                            <div class="mt-0.5 text-[14px] text-muted">২–৩ কর্মদিবস</div>
                        </div>
                        <div class="text-[16px] font-semibold text-espresso">{{ bn_price($general['delivery_outside']) }}</div>
                    </div>
                    <div class="text-[13px] text-muted">শহর অনুযায়ী ডেলিভারি চার্জ নির্ধারিত হয়।@if($general['free_delivery_threshold'] > 0) {{ bn_price($general['free_delivery_threshold']) }} বা তার বেশি অর্ডারে ফ্রি।@endif</div>
                </div>
            </div>

            {{-- Payment --}}
            <div class="sm:bg-panel sm:rounded-[22px] sm:p-[26px]">
                <div class="text-[16.5px] sm:text-[18px] font-semibold">পেমেন্ট</div>
                <div class="mt-3 sm:mt-4 flex flex-col gap-3">
                    @foreach($methods as $option)
                        <label class="rounded-[16px] p-[18px] flex justify-between items-center gap-3 cursor-pointer transition-shadow"
                               :class="$store.checkout.method === @js($option['value']) ? 'bg-white shadow-[inset_0_0_0_2px_#3B2F2D]' : 'bg-panel-2 sm:bg-white'">
                            <input type="radio" name="payment_method" value="{{ $option['value'] }}" x-model="$store.checkout.method" class="sr-only">
                            <span>
                                <span class="block text-[15.5px] font-semibold" :class="$store.checkout.method === @js($option['value']) ? 'text-ink' : 'text-cocoa'">{{ $option['label'] }}</span>
                                <span class="block mt-0.5 text-[14px] text-muted">{{ $option['hint'] }}</span>
                            </span>
                            <span class="w-5 h-5 rounded-full flex-none" :class="$store.checkout.method === @js($option['value']) ? 'bg-espresso' : 'bg-line'"></span>
                        </label>
                    @endforeach
                    <template x-if="$store.checkout.method !== 'cod'">
                        <div class="text-[13px] text-muted">অর্ডার নিশ্চিত করার পর আমাদের টিম পেমেন্টের নির্দেশনা জানিয়ে দেবে।</div>
                    </template>
                </div>
            </div>

            <div class="sm:bg-panel sm:rounded-[22px] sm:p-[26px]">
                <label class="nf-label" for="co-notes">অর্ডার নোট <span class="text-muted">(ঐচ্ছিক)</span></label>
                <textarea id="co-notes" name="notes" rows="2" maxlength="500" class="nf-input nf-input-soft sm:bg-white" placeholder="যেমন: বিকেল ৫টার পরে ডেলিভারি চাই">{{ old('notes') }}</textarea>
            </div>
        </div>

        {{-- Summary --}}
        <div class="bg-panel rounded-[22px] p-[18px] sm:p-[26px] desk:sticky desk:top-28">
            <div class="text-[16.5px] sm:text-[18px] font-semibold">অর্ডার সারাংশ</div>
            <div class="mt-4 sm:mt-[18px] flex flex-col gap-3.5">
                @foreach($items as $line)
                    @php $lineVariant = $line['variant']; @endphp
                    <div class="flex gap-3 items-center">
                        <x-ui.product-image :product="$lineVariant->product" :variant="$lineVariant" class="w-[52px] h-[60px] rounded-[10px] flex-none" />
                        <div class="flex-1 min-w-0">
                            <div class="text-[15px] font-semibold truncate">{{ $lineVariant->product->name }}</div>
                            <div class="text-[13.5px] text-muted">{{ bn_digits($lineVariant->shortLabel()) }} · {{ bn_digits($line['quantity']) }}টি</div>
                        </div>
                        <div class="text-[15px] font-semibold">{{ bn_price($line['line_total']) }}</div>
                    </div>
                @endforeach
            </div>
            <div class="nf-line my-[18px]"></div>
            <div class="flex flex-col gap-2.5 sm:gap-3 text-[14.5px] sm:text-[15px] text-cocoa">
                <div class="flex justify-between"><span>সাবটোটাল</span><span class="text-ink">{{ bn_price($summary['subtotal']) }}</span></div>
                @if($summary['discount'] > 0)
                    <div class="flex justify-between"><span>ছাড় ({{ $summary['coupon'] }})</span><span class="text-accent">−{{ bn_price($summary['discount']) }}</span></div>
                @endif
                <div class="flex justify-between"><span>ডেলিভারি</span><span class="text-ink" x-text="$store.checkout.delivery > 0 ? '৳' + bnNumber($store.checkout.delivery) : 'ফ্রি'"></span></div>
            </div>
            <div class="nf-line my-[18px]"></div>
            <div class="flex justify-between items-baseline">
                <span class="text-[17px] font-semibold">সর্বমোট</span>
                <span class="text-[24px] font-semibold text-espresso" x-text="'৳' + bnNumber($store.checkout.total)"></span>
            </div>
            <button class="hidden sm:block mt-5 w-full bg-espresso text-white rounded-full py-[17px] text-[15.5px] font-semibold hover:bg-ink">অর্ডার নিশ্চিত করুন</button>
            <div class="mt-3 text-[13px] leading-[1.7] text-muted">
                অর্ডার করলে আপনি আমাদের <a href="{{ route('store.page', 'terms') }}" class="underline">শর্তাবলি</a> ও
                <a href="{{ route('store.page', 'privacy') }}" class="underline">প্রাইভেসি পলিসিতে</a> সম্মত হচ্ছেন।
            </div>
        </div>

        {{-- Sticky confirm bar (phones) --}}
        <div class="sm:hidden fixed inset-x-0 bottom-0 z-50 bg-white px-5 pt-3 pb-[max(12px,env(safe-area-inset-bottom))] flex gap-3 items-center shadow-[0_-8px_22px_-20px_rgba(36,28,26,.9)]">
            <div>
                <div class="text-[18px] font-semibold text-espresso" x-text="'৳' + bnNumber($store.checkout.total)"></div>
                <div class="text-[12px] text-muted" x-text="$store.checkout.method === 'cod' ? 'COD' : 'পেমেন্ট'"></div>
            </div>
            <button class="flex-1 bg-espresso text-white rounded-full py-[15px] text-[15px] font-semibold">অর্ডার নিশ্চিত করুন</button>
        </div>
    </form>
</div>
@endsection

@push('head')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('checkout', {
            city: @json(old('city', $prefill['city'])),
            name: @json(old('name', $prefill['name'])),
            phone: @json(old('phone', $prefill['phone'])),
            address: @json(old('address', $prefill['address'])),
            area: @json(old('area', $prefill['area'])),
            method: @json($selectedMethod),
            subtotal: {{ $summary['subtotal'] }},
            discount: {{ $summary['discount'] }},
            rates: { inside: {{ (float) $general['delivery_inside'] }}, outside: {{ (float) $general['delivery_outside'] }}, free: {{ (float) $general['free_delivery_threshold'] }} },
            get zone() { return this.city === @json($insideCity) ? 'inside' : 'outside'; },
            get delivery() { return this.rates.free > 0 && this.subtotal >= this.rates.free ? 0 : this.rates[this.zone]; },
            get total() { return Math.max(0, this.subtotal - this.discount + this.delivery); },
            get step() {
                if (! (this.name && this.phone)) return 1;
                if (! (this.address && this.area)) return 2;
                return 3;
            },
            get stepLabel() { return ['তথ্য', 'ডেলিভারি', 'পেমেন্ট'][this.step - 1]; },
        });
    });
</script>
@endpush
