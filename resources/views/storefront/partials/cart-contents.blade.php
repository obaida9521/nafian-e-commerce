@inject('cart', 'App\Services\CartService')
@php
    $lines = $cart->getItems();
    $summary = $cart->getSummary();
@endphp
<div class="sm:hidden w-10 h-1 rounded bg-[#E2DBD6] mx-auto mt-2.5"></div>
<div class="flex items-center justify-between px-6 pt-4 pb-3 sm:py-6 flex-none">
    <div class="text-[19px] font-semibold">আপনার ব্যাগ <span class="text-muted font-normal text-[15px]">· {{ bn_digits($cart->getCount()) }}টি</span></div>
    <button @click="cartOpen=false" class="w-9 h-9 rounded-full bg-sand text-espresso text-sm" aria-label="বন্ধ করুন">✕</button>
</div>

@if($lines->isNotEmpty())
    <div class="flex-1 overflow-y-auto px-6 flex flex-col gap-3">
        @foreach($lines as $line)
            @include('storefront.partials.bag-line', ['line' => $line, 'compact' => true])
        @endforeach
    </div>

    <div class="flex-none px-6 pt-4 pb-[max(20px,env(safe-area-inset-bottom))]">
        <div class="flex flex-col gap-2.5 text-[14.5px] text-cocoa">
            <div class="flex justify-between"><span>সাবটোটাল</span><span class="text-ink">{{ bn_price($summary['subtotal']) }}</span></div>
            @if($summary['discount'] > 0)
                <div class="flex justify-between"><span>ছাড় ({{ $summary['coupon'] }})</span><span class="text-accent">−{{ bn_price($summary['discount']) }}</span></div>
            @endif
            <div class="flex justify-between"><span>ডেলিভারি ({{ $summary['zone'] === 'outside' ? 'ঢাকার বাইরে' : 'ঢাকা' }})</span><span class="text-ink">{{ $summary['delivery'] > 0 ? bn_price($summary['delivery']) : 'ফ্রি' }}</span></div>
        </div>
        <div class="nf-line my-4"></div>
        <div class="flex justify-between items-baseline"><span class="text-[16px] font-semibold">সর্বমোট</span><span class="text-[22px] font-semibold text-espresso">{{ bn_price($summary['total']) }}</span></div>
        <div class="mt-4 grid grid-cols-[1fr_2fr] gap-2.5">
            <a href="{{ route('store.cart') }}" class="bg-sand-3 text-espresso rounded-full py-[15px] text-center text-[15px] font-semibold">ব্যাগ দেখুন</a>
            <a href="{{ route('store.checkout') }}" class="bg-espresso text-white rounded-full py-[15px] text-center text-[15px] font-semibold hover:bg-ink">চেকআউটে যান</a>
        </div>
    </div>
@else
    <div class="flex-1 flex flex-col items-center justify-center px-10 py-14 text-center">
        <div class="w-16 h-16 rounded-full bg-sand grid place-items-center text-2xl text-espresso">⌀</div>
        <div class="mt-4 font-display text-[24px]">ব্যাগ খালি</div>
        <div class="mt-1.5 text-[14.5px] text-muted">পছন্দের সুগন্ধি বেছে নিন।</div>
        <a href="{{ route('store.shop') }}" @click="cartOpen=false" class="mt-6 bg-espresso text-white rounded-full px-8 py-3.5 text-[14.5px] font-semibold">কেনাকাটা শুরু করুন</a>
    </div>
@endif
