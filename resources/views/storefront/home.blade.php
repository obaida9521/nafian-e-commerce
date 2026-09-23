@extends('layouts.app')
@section('title', 'সকাল পর্যন্ত থেকে যায় যে ঘ্রাণ')

@if($campaign['enabled'] && $campaignCoupon)
    @section('announcement')
        <span>ঢাকায় ২৪ ঘণ্টায় ডেলিভারি</span><span class="opacity-40">•</span>
        <span>ক্যাশ অন ডেলিভারি</span><span class="opacity-40">•</span>
        <a href="{{ route('store.offers') }}" class="text-sky hover:underline">{{ $campaign['title'] }}</a>
    @endsection
@endif

@php
    $daysLeft = $campaignCoupon?->valid_until && $campaignCoupon->valid_until->isFuture()
        ? (int) ceil(now()->diffInDays($campaignCoupon->valid_until, absolute: true))
        : null;
    $kpis = [
        ['অরিজিনাল গ্যারান্টি', 'প্রতিটি বোতলে ব্যাচ কোড'],
        ['ক্যাশ অন ডেলিভারি', 'হাতে পেয়ে টাকা দিন'],
        ['ডেলিভারি চার্জ', 'ঢাকা '.bn_price($general['delivery_inside']).' · বাইরে '.bn_price($general['delivery_outside'])],
        ['৭ দিনের রিটার্ন', 'সিল না ভাঙলে ফেরত'],
    ];
@endphp

@section('content')
{{-- ───────────────────────── Phone ───────────────────────── --}}
<div class="sm:hidden nf-fade">
    <div class="px-5">
        <div class="relative rounded-[24px] overflow-hidden h-[252px] bg-[#2A2220]">
            <div class="absolute inset-0 nf-drift" style="background:linear-gradient(150deg,#3B2F2D,#5A4842 55%,#2F3B47);"></div>
            @include('storefront.partials.hero-video')
            <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(26,20,19,.15),rgba(26,20,19,.85));"></div>
            <div class="relative h-full flex flex-col justify-end p-[22px]">
                <div class="text-[11px] font-medium tracking-[0.2em] text-sky">সিগনেচার</div>
                <h1 class="mt-2 font-display text-[27px] leading-[1.16] text-white">সকাল পর্যন্ত থেকে যায় যে ঘ্রাণ</h1>
                <a href="{{ route('store.shop') }}" class="mt-4 self-start bg-white text-espresso rounded-full px-[26px] py-[13px] text-[14px] font-semibold">কালেকশন দেখুন</a>
            </div>
        </div>
    </div>

    <div class="mt-4 px-5 nf-rail gap-[9px]">
        <a href="{{ route('store.shop') }}" class="flex-none bg-espresso text-white rounded-full px-[17px] py-[9px] text-[13.5px] font-medium">সব</a>
        @foreach(nav_categories() as $navCategory)
            <a href="{{ route('store.shop.category', $navCategory->slug) }}" class="flex-none bg-sand-3 rounded-full px-[17px] py-[9px] text-[13.5px] font-medium whitespace-nowrap">{{ $navCategory->name }}</a>
        @endforeach
    </div>

    <div class="mt-5 px-5 flex items-baseline justify-between">
        <h2 class="text-[19px] font-semibold">বেস্ট সেলার</h2>
        <a href="{{ route('store.shop', ['sort' => 'best_selling']) }}" class="text-[13.5px] font-medium text-accent">সব দেখুন</a>
    </div>
    <div class="mt-3 px-5 nf-rail gap-3 pb-1">
        @foreach($bestSellers as $product)
            @include('storefront.partials.product-card', ['product' => $product, 'style' => 'rail'])
        @endforeach
    </div>

    @if($campaign['enabled'] && $campaignCoupon)
        <div class="mt-[22px] px-5">
            <a href="{{ route('store.offers') }}" class="block rounded-[20px] p-[22px] text-[#F1ECE9]" style="background:linear-gradient(140deg,#3B2F2D,#33444F);">
                <div class="text-[11px] font-medium tracking-[0.2em] text-sky">{{ $campaign['eyebrow'] ?: 'চলতি অফার' }}</div>
                <div class="mt-2 font-display text-[24px] leading-[1.25]">{{ $campaign['title'] }}</div>
                <div class="mt-3.5 flex gap-2.5 items-center">
                    <span class="bg-white/15 rounded-[10px] px-3.5 py-[9px] text-[14px] font-semibold tracking-[0.05em]">{{ $campaignCoupon->code }}</span>
                    @if($daysLeft !== null)<span class="text-[13px] text-[#C3B9B4]">{{ bn_digits($daysLeft) }} দিন বাকি</span>@endif
                </div>
            </a>
        </div>
    @endif

    <div class="mt-6 px-5 grid grid-cols-2 gap-3">
        @foreach($kpis as [$title, $body])
            <div class="bg-panel-2 rounded-[18px] p-4">
                <div class="text-[14px] font-semibold text-espresso">{{ $title }}</div>
                <div class="mt-1 text-[12.5px] text-muted leading-snug">{{ $body }}</div>
            </div>
        @endforeach
    </div>
</div>

{{-- ───────────────────────── Desktop ───────────────────────── --}}
<div class="hidden sm:block nf-fade">
    <div class="px-8 pt-2">
        <div class="relative rounded-[28px] overflow-hidden min-h-[620px] max-[640px]:min-h-0 bg-[#2A2220] shadow-[0_24px_60px_-30px_rgba(59,47,45,.45)]">
            <div class="absolute inset-0 nf-drift" style="background:linear-gradient(120deg,#3B2F2D 0%,#5A4842 45%,#2F3B47 100%);"></div>
            @include('storefront.partials.hero-video')
            <div class="absolute inset-0" style="background:linear-gradient(90deg,rgba(26,20,19,.82) 0%,rgba(26,20,19,.45) 55%,rgba(26,20,19,.15) 100%);"></div>
            <div class="relative px-[72px] py-24 max-w-[760px] flex flex-col justify-center min-h-[620px]">
                <div class="text-[13px] font-medium tracking-[0.24em] text-sky">SIGNATURE COLLECTION</div>
                <h1 class="mt-5 font-display text-[64px] leading-[1.08] text-white">সকাল পর্যন্ত থেকে যায় যে ঘ্রাণ</h1>
                <p class="mt-5 text-[18px] font-light leading-[1.85] text-[#E2D9D4] max-w-[46ch]">
                    ছোট ব্যাচে তৈরি, যত্নে বাছাই করা উপাদান। বোতলজাত করার আগে প্রতিটি ব্যাচ ছয় সপ্তাহ রাখা হয়।
                </p>
                <div class="mt-8 flex gap-3.5 flex-wrap">
                    <a href="{{ route('store.shop') }}" class="bg-white text-espresso rounded-full px-9 py-[17px] text-[15px] font-semibold shadow-[0_12px_30px_-14px_rgba(0,0,0,.6)]">কালেকশন দেখুন</a>
                    <a href="{{ route('store.shop', ['sort' => 'top_rated']) }}" class="bg-white/10 backdrop-blur text-white rounded-full px-8 py-[17px] text-[15px] font-medium">আপনার সুগন্ধি খুঁজুন</a>
                </div>
            </div>
        </div>
    </div>

    <div class="px-8 pt-7 grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-4">
        @foreach($kpis as [$title, $body])
            <div class="bg-panel-2 rounded-[18px] px-6 py-[22px]">
                <div class="text-[15.5px] font-semibold text-espresso">{{ $title }}</div>
                <div class="mt-1 text-[14px] text-muted">{{ $body }}</div>
            </div>
        @endforeach
    </div>

    <div class="px-8 pt-[72px]">
        <div class="flex items-end justify-between gap-5 flex-wrap">
            <div>
                <h2 class="font-display text-[38px]">বেস্ট সেলার</h2>
                <div class="mt-1.5 text-[15px] text-muted">গত ৩০ দিনে সবচেয়ে বেশি বিক্রি</div>
            </div>
            <a href="{{ route('store.shop', ['sort' => 'best_selling']) }}" class="text-[15px] font-medium text-accent hover:underline">সব দেখুন →</a>
        </div>
        <div class="mt-7 grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-6">
            @foreach($bestSellers as $product)
                @include('storefront.partials.product-card', ['product' => $product, 'reviewWord' => true])
            @endforeach
        </div>
    </div>

    @if($collections->isNotEmpty())
        <div class="px-8 pt-[72px]">
            <h2 class="font-display text-[38px]">কালেকশন</h2>
            <div class="mt-[26px] grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-5">
                @foreach($collections as $collection)
                    @php
                        $meta = collect([
                            $collection->products_count ? bn_digits($collection->products_count).'টি পণ্য' : null,
                            $collection->min_price ? bn_price($collection->min_price).' থেকে' : null,
                        ])->filter()->implode(' · ');
                    @endphp
                    @php $dark = $loop->first || $collection->image_url; @endphp
                    <a href="{{ route('store.shop.category', $collection->slug) }}"
                       @class(['group relative overflow-hidden min-w-0 rounded-[24px] min-h-[320px] flex flex-col justify-end', 'text-[#F3EDE9]' => $dark, 'p-[34px] shadow-[0_18px_44px_-28px_rgba(36,28,26,.7)]' => $loop->first, 'p-7' => ! $loop->first])
                       style="background:{{ $loop->first ? 'linear-gradient(150deg,#3B2F2D,#5A4842)' : ($loop->index === 1 ? 'linear-gradient(150deg,#F4F1EE,#E9E4DF)' : 'linear-gradient(150deg,#EFF2F5,#E3E9EE)') }};">
                        @if($collection->image_url)
                            {{-- Category photo with a bottom scrim so the name stays readable. --}}
                            <img src="{{ $collection->image_url }}" alt="" loading="lazy"
                                 class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
                            <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(26,20,19,0) 35%,rgba(26,20,19,.78) 100%);"></div>
                        @endif
                        <div @class(['relative font-display', 'text-[30px]' => $loop->first, 'text-[24px]' => ! $loop->first])>{{ $collection->name }}</div>
                        <div @class(['relative mt-1.5 text-[14px]', 'text-[15px] text-[#E2D9D4]' => $dark, 'text-muted' => ! $dark])>{{ $meta }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mx-8 mt-[72px] bg-panel-2 rounded-[28px] p-14 grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-12 items-center">
        <div class="rounded-[20px] min-h-[380px]" style="background:linear-gradient(150deg,#EDE8E3,#E2DBD5);"></div>
        <div>
            <div class="text-[13px] font-medium tracking-[0.22em] text-accent">আমাদের গল্প</div>
            <h2 class="mt-3.5 font-display text-[38px] leading-[1.3]">ঢাকায় ছোট ব্যাচে তৈরি সুগন্ধি</h2>
            <p class="mt-4 text-[16.5px] leading-[1.9] text-cocoa">সিলেট থেকে ঊদ, তায়েফ থেকে গোলাপ অ্যাবসলিউট, সার্টিফায়েড মহীশূর চন্দন। বোতলজাত করার আগে প্রতিটি ব্যাচ ছয় সপ্তাহ রাখা হয়।</p>
            <p class="mt-3 text-[16.5px] leading-[1.9] text-cocoa">দাম মেলাতে আমরা ফর্মুলা বদলাই না। উপাদান না পেলে সেই সুগন্ধি বানানো বন্ধ রাখি।</p>
            <a href="{{ route('store.page', 'about') }}" class="inline-block mt-[26px] bg-espresso text-white rounded-full px-8 py-[15px] text-[14.5px] font-semibold hover:bg-ink">গল্পটি পড়ুন</a>
        </div>
    </div>

    @if($reviews->isNotEmpty())
        <div class="px-8 pt-[72px]">
            <div class="flex items-baseline gap-4 flex-wrap">
                <h2 class="font-display text-[38px]">ক্রেতাদের মতামত</h2>
                <span class="text-[15px] text-muted">গড় {{ bn_digits(number_format($reviewStats['average'], 1)) }} · {{ bn_digits($reviewStats['count']) }} রিভিউ</span>
            </div>
            <div class="mt-[26px] grid grid-cols-[repeat(auto-fit,minmax(280px,1fr))] gap-5">
                @foreach($reviews as $review)
                    <div class="bg-panel-2 rounded-[20px] p-7">
                        <div class="text-[14px] text-espresso">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</div>
                        <p class="mt-3 text-[16.5px] leading-[1.85] text-ink line-clamp-4">{{ $review->body }}</p>
                        <div class="mt-4 text-[14.5px] font-semibold">
                            {{ $review->user->name }} <span class="font-normal text-muted">· {{ $review->product->name }}</span>
                        </div>
                        <div class="mt-0.5 text-[13px] text-accent">ভেরিফাইড ক্রেতা</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mx-8 mt-[72px] rounded-[28px] p-14 text-[#F1ECE9] grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-9 items-center" style="background:linear-gradient(140deg,#3B2F2D,#33444F);">
        <div>
            <h2 class="font-display text-[30px]">মাসে দুটি চিঠি</h2>
            <p class="mt-2.5 text-[16px] text-[#C3B9B4]">নতুন রিলিজ, রিস্টক আর সোর্সিং নিয়ে ছোট নোট। অপ্রয়োজনীয় ডিসকাউন্ট মেইল নয়।</p>
        </div>
        @include('storefront.partials.subscribe-form', [
            'source' => 'newsletter',
            'placeholder' => 'আপনার ইমেইল',
            'button' => 'সাবস্ক্রাইব',
            'type' => 'email',
        ])
    </div>
</div>
@endsection
