@extends('layouts.app')
@section('title', 'Considered pieces for a quieter wardrobe')

@section('content')
<div class="nf-fade">
    {{-- Hero --}}
    <section class="max-w-[1280px] mx-auto px-4 sm:px-6">
        <div class="grid md:grid-cols-2 items-center gap-8 md:gap-12 pt-4 pb-8 md:py-16">
            <div class="hidden md:block order-2 md:order-1">
                <div class="text-xs tracking-[0.2em] uppercase text-[#B45309] font-semibold mb-3.5">Autumn Collection 2026</div>
                <h1 class="text-[40px] md:text-[64px] leading-[1.05] font-semibold tracking-tight mb-3.5">Considered pieces for a quieter wardrobe.</h1>
                <p class="text-sm leading-relaxed text-gray-500 max-w-[440px] mb-7">Modern essentials in leather, wool and silk — made in small batches, designed to outlast the season.</p>
                <div class="flex gap-3.5 flex-wrap">
                    <a href="{{ route('store.shop') }}" class="h-[52px] px-7 rounded-lg bg-[#691d2a] hover:bg-[#4d141e] text-white text-sm font-semibold flex items-center">Shop the collection</a>
                    <a href="{{ route('store.shop') }}" class="h-[52px] px-6 rounded-lg border border-[#691d2a] text-[#691d2a] hover:bg-[#691d2a] hover:text-white text-sm font-semibold flex items-center">Lookbook</a>
                </div>
            </div>
            <div class="relative h-[320px] md:h-[460px] rounded-[14px] overflow-hidden order-1 md:order-2" style="background:linear-gradient(150deg,#D8CBB6,#B7A98F 70%);">
                <div class="absolute inset-0" style="background-image:repeating-linear-gradient(135deg,rgba(255,255,255,0.06) 0 2px,transparent 2px 22px);"></div>
                <div class="absolute left-5 bottom-[18px] font-mono text-[11px] tracking-wide text-[#691d2a]/55 bg-[#FAFAF8]/70 px-2.5 py-[5px] rounded-[5px]">LIFESTYLE · HERO 3:4</div>
            </div>
        </div>
    </section>

    {{-- Featured categories --}}
    @if($homeCategories->isNotEmpty())
    <section class="max-w-[1280px] mx-auto px-6 pt-2 pb-14">
        <div class="flex items-baseline justify-between mb-5.5">
            <h2 class="text-2xl md:text-3xl font-semibold tracking-tight">Shop by category</h2>
            <a href="{{ route('store.shop') }}" class="text-sm text-gray-500 hover:text-[#691d2a] font-medium">View all →</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-[18px]">
            @foreach($homeCategories as $c)
                @php $catImg = $c->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($c->image_path) : null; @endphp
                <a href="{{ route('store.shop.category', $c->slug) }}" class="rounded-xl overflow-hidden">
                    <div class="h-[200px] rounded-xl relative overflow-hidden hover:brightness-[0.97]" style="background:linear-gradient(150deg,{{ $c->tone ?? '#E7DFD2' }},{{ $c->tone2 ?? '#CFC2AC' }});">
                        @if($catImg)
                            <img src="{{ $catImg }}" alt="{{ $c->name }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
                        @else
                            <div class="absolute inset-0 rounded-xl" style="background-image:repeating-linear-gradient(135deg,rgba(255,255,255,0.05) 0 2px,transparent 2px 20px);"></div>
                        @endif
                    </div>
                    <div class="flex items-baseline justify-between px-1 pt-3">
                        <span class="font-semibold text-[15px]">{{ $c->name }}</span>
                        <span class="text-[13px] text-gray-400 font-mono">{{ $c->products_count }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Best sellers --}}
    <section class="max-w-[1280px] mx-auto px-6 pt-2 pb-16">
        <div class="flex items-baseline justify-between mb-5.5">
            <h2 class="text-2xl md:text-3xl font-semibold tracking-tight">Best Selling product</h2>
            <a href="{{ route('store.shop') }}" class="text-sm text-gray-500 hover:text-[#691d2a] font-medium">All products →</a>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-[22px]">
            @foreach($bestSellers as $product)
                @include('storefront.partials.product-card', ['product' => $product])
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section class="bg-[#fff2e3]">
        <div class="max-w-[1280px] mx-auto px-4 py-12 md:py-16">
            <h2 class="text-xl md:text-3xl font-semibold text-center mb-7 md:mb-9 tracking-tight text-gray-900">How Nafian works</h2>
            <div class="grid grid-cols-3 gap-3 md:gap-8">
                @foreach([['1','Choose with intention','Curated essentials, never fast fashion. Each piece earns its place.'],['2','Made in small batches','Crafted in limited runs from leather, wool and silk that lasts.'],['3','Delivered with care','Carbon-neutral shipping and easy 30-day returns, always.']] as $s)
                    <div class="text-center">
                        <div class="w-9 h-9 md:w-[46px] md:h-[46px] rounded-full border border-[#691d2a]/30 flex items-center justify-center mx-auto mb-2.5 md:mb-4.5 font-mono text-[#691d2a] text-[13px] md:text-[15px]">{{ $s[0] }}</div>
                        <div class="text-[13px] md:text-lg font-semibold mb-1 md:mb-2 text-gray-900 leading-tight">{{ $s[1] }}</div>
                        <div class="text-[11px] md:text-sm leading-snug md:leading-relaxed text-gray-500 max-w-[260px] mx-auto">{{ $s[2] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Trust badges --}}
    <section class="max-w-[1280px] mx-auto px-4 py-11">
        <div class="grid md:grid-cols-3 gap-[18px]">
            @foreach([['Free shipping over ৳200','On all orders, delivered carbon-neutral.'],['30-day returns','Not right? Send it back, no questions asked.'],['Crafted to last','Designed in studio, made in small batches.']] as $t)
                <div class="flex gap-3.5 items-start p-5.5 border border-[#EADBC4] rounded-xl bg-white">
                    <div class="text-[#691d2a] flex-none mt-0.5">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <div>
                        <div class="font-semibold text-[15px] mb-0.5">{{ $t[0] }}</div>
                        <div class="text-[13px] text-gray-500 leading-snug">{{ $t[1] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Newsletter — hidden per request
    <section class="max-w-[1280px] mx-auto px-4 pt-6 pb-16">
        <div class="bg-[#F8EAD6] rounded-2xl px-5 py-12 text-center">
            <h2 class="text-3xl font-semibold tracking-tight mb-2.5">Join the list</h2>
            <p class="text-[15px] text-gray-500 mb-6.5">Early access to new drops and 10% off your first order.</p>
            <form class="flex gap-2.5 max-w-[430px] mx-auto" @submit.prevent="pushToast('success', $refs.nl.value ? `You're on the list — check your inbox.` : 'Enter an email address.')">
                <input x-ref="nl" type="email" placeholder="Email address" class="flex-1 h-12 border border-[#EADBC4] rounded-lg px-4 text-[15px] bg-white outline-none focus:border-[#691d2a]">
                <button class="h-12 px-6.5 rounded-lg bg-[#D4A853] hover:bg-[#c79a44] text-[#691d2a] text-[15px] font-semibold">Subscribe</button>
            </form>
        </div>
    </section>
    --}}
</div>
@endsection
