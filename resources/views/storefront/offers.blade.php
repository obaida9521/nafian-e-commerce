@extends('layouts.app')
@section('title', $campaign['title'] ?: 'অফার')

@section('announcement')
    @if($endsAt)
        <span>অফার শেষ হবে {{ bn_date($endsAt) }}</span><span class="opacity-40">•</span>
    @endif
    @if($coupon)
        <span class="text-sky">কোড {{ $coupon->code }} · {{ $coupon->summaryBn() }}</span>
    @else
        <span>ঢাকায় ২৪ ঘণ্টায় ডেলিভারি</span><span class="opacity-40">•</span><span>ক্যাশ অন ডেলিভারি</span>
    @endif
@endsection

@section('content')
<div class="nf-fade">
    {{-- Campaign hero --}}
    <div class="px-5 sm:px-8 pt-2">
        <div class="relative rounded-[24px] sm:rounded-[28px] overflow-hidden min-h-[320px] sm:min-h-[440px] bg-[#2A2220] shadow-[0_24px_60px_-30px_rgba(59,47,45,.45)]">
            <div class="absolute inset-0 nf-drift" style="background:linear-gradient(120deg,#33444F 0%,#5A4842 50%,#3B2F2D 100%);"></div>
            <div class="absolute inset-0" style="background:linear-gradient(90deg,rgba(26,20,19,.8) 0%,rgba(26,20,19,.4) 60%,rgba(26,20,19,.12) 100%);"></div>
            <div class="relative px-[22px] py-10 sm:px-[72px] sm:py-24 max-w-[720px] flex flex-col justify-center min-h-[320px] sm:min-h-[440px]">
                @if($campaign['eyebrow'])
                    <div class="text-[11px] sm:text-[13px] font-medium tracking-[0.2em] sm:tracking-[0.24em] text-sky">{{ $campaign['eyebrow'] }}</div>
                @endif
                <h1 class="mt-3 sm:mt-[18px] font-display text-[34px] sm:text-[64px] leading-[1.1] text-white">{{ $campaign['title'] }}</h1>
                @if($campaign['body'])
                    <p class="mt-4 text-[15.5px] sm:text-[17.5px] font-light leading-[1.85] text-[#E2D9D4] max-w-[44ch]">{{ $campaign['body'] }}</p>
                @endif
                <div class="mt-6 sm:mt-7 flex gap-3 items-center flex-wrap">
                    @if($coupon)
                        <span class="bg-white/15 text-white rounded-[14px] px-[22px] py-3.5 text-[15px] sm:text-[17px] font-semibold tracking-[0.06em]">{{ $coupon->code }}</span>
                    @endif
                    <a href="#deals" class="bg-white text-espresso rounded-full px-[34px] py-4 text-[15px] font-semibold">অফার দেখুন</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Countdown --}}
    @if($endsAt)
        <div class="px-5 sm:px-8 pt-[26px]" x-data="{
            left: {},
            tick() {
                const diff = Math.max(0, new Date(@js($endsAt->toIso8601String())) - new Date());
                this.left = {
                    days: Math.floor(diff / 86400000),
                    hours: Math.floor(diff / 3600000) % 24,
                    minutes: Math.floor(diff / 60000) % 60,
                };
            }
        }" x-init="tick(); setInterval(() => tick(), 30000)">
            <div class="bg-panel rounded-[22px] px-[26px] py-[22px] flex justify-between items-center gap-5 flex-wrap">
                <div class="text-[16px] sm:text-[17px] font-semibold">অফার শেষ হতে বাকি</div>
                <div class="flex gap-2.5 flex-wrap">
                    @foreach(['days' => 'দিন', 'hours' => 'ঘণ্টা', 'minutes' => 'মিনিট'] as $key => $label)
                        <div class="bg-white rounded-[14px] px-5 py-3 text-center min-w-[76px]">
                            <div class="text-[24px] font-semibold text-espresso" x-text="bnNumber(left.{{ $key }} ?? 0).padStart(2, '০')">—</div>
                            <div class="text-[12.5px] text-muted">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Combo packs --}}
    @if($combos->isNotEmpty())
        <div class="px-5 sm:px-8 pt-12 sm:pt-14">
            <h2 class="font-display text-[26px] sm:text-[38px]">কম্বো প্যাক</h2>
            <div class="mt-1.5 text-[15px] text-muted">আলাদা কেনার চেয়ে সাশ্রয়</div>

            <div class="mt-6 grid gap-5 sm:gap-[22px] sm:grid-cols-[repeat(auto-fit,minmax(240px,1fr))] desk:grid-cols-3">
                @foreach($combos as $combo)
                    @php
                        $comboVariants = $combo->variants->where('is_active', true)->values();
                        $cheapest = $comboVariants->sortBy('price')->first();
                        $saving = (float) ($cheapest?->compare_at_price ?? 0) - (float) ($cheapest?->price ?? 0);
                        $comboStock = $comboVariants->contains(fn ($v) => $v->available_quantity > 0);
                        $single = $comboVariants->count() === 1;
                    @endphp
                    <div class="rounded-[22px] overflow-hidden bg-white nf-shadow flex flex-col">
                        <a href="{{ route('store.product', $combo->slug) }}">
                            <x-ui.product-image :product="$combo" hover class="aspect-[1/0.78]">
                                @if($saving > 0)
                                    <span class="absolute left-3.5 top-3.5 bg-espresso text-white rounded-full px-3.5 py-1.5 text-[12.5px] font-semibold">সাশ্রয় {{ bn_price($saving) }}</span>
                                @elseif($combo->badge)
                                    <span class="absolute left-3.5 top-3.5 bg-accent text-white rounded-full px-3.5 py-1.5 text-[12.5px] font-semibold">{{ $combo->badge }}</span>
                                @endif
                            </x-ui.product-image>
                        </a>
                        <div class="px-[22px] pt-5 pb-6 flex flex-col flex-1">
                            <a href="{{ route('store.product', $combo->slug) }}" class="text-[20px] font-semibold hover:text-accent">{{ $combo->name }}</a>
                            @if($combo->short_description)
                                <div class="mt-1.5 text-[14.5px] text-muted leading-[1.7]">{{ $combo->short_description }}</div>
                            @endif
                            <div class="mt-3.5 flex items-baseline gap-2.5">
                                <span class="text-[22px] font-semibold text-espresso">{{ bn_price($cheapest?->price) }}</span>
                                @if($saving > 0)<span class="text-[15px] text-muted line-through">{{ bn_price($cheapest->compare_at_price) }}</span>@endif
                            </div>
                            <div class="mt-auto pt-4">
                                @if($comboStock && $single)
                                    <form method="POST" action="{{ route('store.cart.store') }}" class="js-cart-form">
                                        @csrf
                                        <input type="hidden" name="variant_id" value="{{ $comboVariants->firstWhere(fn ($v) => $v->available_quantity > 0)?->id }}">
                                        <button class="w-full bg-espresso text-white rounded-full py-[15px] text-[15px] font-semibold hover:bg-ink">ব্যাগে যোগ করুন</button>
                                    </form>
                                @elseif($comboStock)
                                    <a href="{{ route('store.product', $combo->slug) }}" class="block text-center w-full bg-espresso text-white rounded-full py-[15px] text-[15px] font-semibold hover:bg-ink">অপশন দেখুন</a>
                                @else
                                    <span class="block text-center w-full bg-sand text-muted rounded-full py-[15px] text-[15px] font-semibold">স্টকে নেই</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- On-sale products --}}
    @if($deals->isNotEmpty())
        <div id="deals" class="px-5 sm:px-8 pt-12 sm:pt-14 scroll-mt-24">
            <div class="flex items-end justify-between gap-4 flex-wrap">
                <h2 class="font-display text-[26px] sm:text-[38px]">ছাড় চলছে</h2>
                <a href="{{ route('store.shop', ['on_sale' => 1]) }}" class="text-[15px] font-medium text-accent hover:underline">সব দেখুন →</a>
            </div>
            <div class="mt-6 grid grid-cols-2 gap-3.5 sm:gap-[22px] desk:grid-cols-4">
                @foreach($deals as $deal)
                    @include('storefront.partials.product-card', ['product' => $deal, 'style' => 'mini'])
                @endforeach
            </div>
        </div>
    @endif

    {{-- Build your own set --}}
    <div class="mx-5 sm:mx-8 mt-12 sm:mt-14 bg-panel rounded-[24px] sm:rounded-[28px] p-6 sm:p-14">
        <div class="grid gap-7 desk:grid-cols-2 desk:gap-12 items-center">
            <div>
                <div class="text-[13px] font-medium tracking-[0.22em] text-accent">নিজের সেট বানান</div>
                <h2 class="mt-3.5 font-display text-[26px] sm:text-[38px] leading-[1.3]">তিনটি বেছে নিন, {{ bn_digits(20) }}% ছাড় পান</h2>
                <p class="mt-3.5 text-[15.5px] sm:text-[16.5px] leading-[1.9] text-cocoa">
                    যেকোনো তিনটি পণ্য বেছে নিন। বাক্স ও উপহার কার্ড ফ্রি। ছাড় চেকআউটে নিজে থেকেই যোগ হবে।
                </p>
                <a href="{{ route('store.shop') }}" class="inline-block mt-6 bg-espresso text-white rounded-full px-[34px] py-4 text-[15px] font-semibold hover:bg-ink">সেট বানানো শুরু করুন</a>
            </div>
            <div class="grid grid-cols-3 gap-3">
                @foreach($setPicks as $pick)
                    <a href="{{ route('store.product', $pick->slug) }}">
                        <x-ui.product-image :product="$pick" class="aspect-[1/1.2] rounded-2xl" />
                    </a>
                @endforeach
                <a href="{{ route('store.shop') }}" class="aspect-[1/1.2] rounded-2xl bg-sand-2 grid place-items-center text-[22px] font-medium text-dust">+</a>
            </div>
        </div>
    </div>

    {{-- How it works --}}
    <div class="px-5 sm:px-8 pt-12 sm:pt-14 pb-2">
        <h2 class="font-display text-[24px] sm:text-[30px]">কীভাবে কাজ করে</h2>
        <div class="mt-5 grid gap-[18px] desk:grid-cols-3">
            @foreach([
                ['ধাপ ১', 'পণ্য বেছে নিন', 'দুটি বা তার বেশি পণ্য ব্যাগে যোগ করুন।'],
                ['ধাপ ২', 'কোড লিখুন', 'চেকআউটে '.($coupon->code ?? 'কুপন কোড').' লিখে প্রয়োগ করুন।'],
                ['ধাপ ৩', 'হাতে পেয়ে টাকা দিন', 'ঢাকায় ২৪ ঘণ্টা, বাইরে ২–৩ দিন।'],
            ] as [$step, $title, $body])
                <div class="bg-panel rounded-[20px] p-[26px]">
                    <div class="text-[14px] font-semibold text-accent">{{ $step }}</div>
                    <div class="mt-2 text-[18px] font-semibold">{{ $title }}</div>
                    <div class="mt-1.5 text-[15px] leading-[1.8] text-cocoa">{{ $body }}</div>
                </div>
            @endforeach
        </div>
        <div class="mt-[18px] text-[13.5px] leading-[1.8] text-muted">
            @if($endsAt)অফার {{ bn_date($endsAt) }} পর্যন্ত। @endif
            একটি অর্ডারে একটি কুপন প্রযোজ্য।
            @if($coupon && $coupon->min_order_amount > 0)ন্যূনতম {{ bn_price($coupon->min_order_amount) }} অর্ডারে প্রযোজ্য।@endif
        </div>
    </div>

    {{-- SMS signup --}}
    <div class="mx-5 sm:mx-8 mt-12 sm:mt-14 mb-14 rounded-[24px] sm:rounded-[28px] p-6 sm:p-14 text-[#F1ECE9] flex justify-between items-center gap-8 flex-wrap" style="background:linear-gradient(140deg,#3B2F2D,#33444F);">
        <div>
            <h2 class="font-display text-[24px] sm:text-[28px]">অফার শুরুর আগে জানুন</h2>
            <p class="mt-2 text-[15.5px] sm:text-[16px] text-[#C3B9B4] max-w-[44ch]">নতুন ক্যাম্পেইন শুরুর দিনেই এসএমএস পাঠাই। নম্বরটি শুধু এর জন্যই ব্যবহার হয়।</p>
        </div>
        @include('storefront.partials.subscribe-form', [
            'source' => 'campaign_sms',
            'placeholder' => 'মোবাইল নম্বর',
            'button' => 'জানান',
            'type' => 'tel',
        ])
    </div>
</div>
@endsection
