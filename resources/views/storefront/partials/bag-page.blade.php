@php
    /** @var \Illuminate\Support\Collection $items */
    $zoneLabel = $summary['zone'] === 'outside' ? 'ঢাকার বাইরে' : 'ঢাকা';
@endphp

@if($items->isEmpty())
    <div class="rounded-[22px] bg-panel py-20 px-6 text-center">
        <div class="font-display text-[26px] text-espresso">ব্যাগ খালি</div>
        <p class="mt-2 text-[15px] text-muted">পছন্দের সুগন্ধি বেছে নিন।</p>
        <a href="{{ route('store.shop') }}" class="inline-block mt-5 bg-espresso text-white rounded-full px-7 py-3.5 text-[15px] font-semibold">কেনাকাটা শুরু করুন</a>
    </div>
@else
    <div class="grid desk:grid-cols-[1fr_380px] gap-6 desk:gap-8 items-start">
        <div class="flex flex-col gap-3 sm:gap-3.5">
            @foreach($items as $line)
                <div class="sm:hidden">@include('storefront.partials.bag-line', ['line' => $line, 'compact' => true, 'bagPage' => true])</div>
                <div class="hidden sm:block">@include('storefront.partials.bag-line', ['line' => $line, 'compact' => false, 'bagPage' => true])</div>
            @endforeach

            {{-- Coupon --}}
            <div class="mt-1.5">
                @if($summary['coupon'])
                    <form method="POST" action="{{ route('store.cart.coupon.remove') }}" class="js-cart-form flex items-center justify-between gap-3 bg-accent-soft rounded-full pl-[22px] pr-3 py-2.5">
                        @csrf @method('DELETE')
                        <input type="hidden" name="bag_page" value="1">
                        <span class="text-[14.5px] font-medium text-accent">✓ {{ $summary['coupon'] }} প্রয়োগ হয়েছে</span>
                        <button class="text-[13.5px] text-muted hover:text-rose px-3 py-1.5">সরান</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('store.cart.coupon') }}" class="js-cart-form flex gap-2.5 flex-wrap">
                        @csrf
                        <input type="hidden" name="bag_page" value="1">
                        <input name="code" required placeholder="কুপন কোড লিখুন" aria-label="কুপন কোড"
                               class="flex-1 min-w-[200px] bg-sand rounded-full px-[22px] py-3.5 text-[14.5px] uppercase text-ink placeholder-muted outline-none focus:shadow-[inset_0_0_0_2px_#3B2F2D]">
                        <button class="bg-accent-soft text-accent rounded-full px-7 py-3.5 text-[14.5px] font-semibold">প্রয়োগ করুন</button>
                    </form>
                @endif
            </div>

            <a href="{{ route('store.shop') }}" class="hidden sm:block mt-1 text-[14px] text-accent hover:underline">← কেনাকাটা চালিয়ে যান</a>
        </div>

        {{-- Summary --}}
        <div class="bg-panel rounded-[22px] p-[18px] sm:p-[26px] desk:sticky desk:top-28">
            <div class="text-[16px] sm:text-[18px] font-semibold">অর্ডার সারাংশ</div>
            <div class="mt-4 sm:mt-[18px] flex flex-col gap-2.5 sm:gap-3 text-[14.5px] sm:text-[15px] text-cocoa">
                <div class="flex justify-between"><span>সাবটোটাল</span><span class="text-ink">{{ bn_price($summary['subtotal']) }}</span></div>
                @if($summary['discount'] > 0)
                    <div class="flex justify-between"><span>ছাড়</span><span class="text-accent">−{{ bn_price($summary['discount']) }}</span></div>
                @endif
                <div class="flex justify-between"><span>ডেলিভারি ({{ $zoneLabel }})</span><span class="text-ink">{{ $summary['delivery'] > 0 ? bn_price($summary['delivery']) : 'ফ্রি' }}</span></div>
            </div>
            <div class="nf-line my-[18px]"></div>
            <div class="flex justify-between items-baseline">
                <span class="text-[17px] font-semibold">সর্বমোট</span>
                <span class="text-[24px] font-semibold text-espresso">{{ bn_price($summary['total']) }}</span>
            </div>
            <a href="{{ route('store.checkout') }}" class="hidden sm:block text-center mt-5 w-full bg-espresso text-white rounded-full py-[17px] text-[15.5px] font-semibold hover:bg-ink">চেকআউটে যান</a>
            <div class="mt-3.5 text-[13.5px] leading-[1.7] text-muted">ক্যাশ অন ডেলিভারি সারাদেশে। ৭ দিনের মধ্যে রিটার্ন করা যাবে।</div>
        </div>
    </div>

    {{-- Sticky checkout bar (phones), sitting on top of the tab bar --}}
    <div class="sm:hidden fixed inset-x-0 bottom-[var(--nf-tabbar-h)] z-50 bg-white px-5 py-3 flex gap-3 items-center border-b border-hair shadow-[0_-8px_22px_-20px_rgba(36,28,26,.9)]">
        <div>
            <div class="text-[18px] font-semibold text-espresso">{{ bn_price($summary['total']) }}</div>
            <div class="text-[12px] text-muted">সর্বমোট</div>
        </div>
        <a href="{{ route('store.checkout') }}" class="flex-1 text-center bg-espresso text-white rounded-full py-[15px] text-[15px] font-semibold">চেকআউটে যান</a>
    </div>
@endif
