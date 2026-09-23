@extends('layouts.app')
@section('title', 'অর্ডার ট্র্যাক করুন')
@section('hide_header_search', true)

@section('mobile_header')
    <div class="sm:hidden sticky top-0 z-40 bg-white flex items-center gap-3 px-5 pt-3 pb-3">
        <a href="{{ route('store.home') }}" class="text-[17px] font-medium" aria-label="হোম">←</a>
        <div class="text-[20px] font-semibold">অর্ডার ট্র্যাক</div>
    </div>
@endsection

@section('content')
<div class="px-5 sm:px-8 pt-5 sm:pt-[34px] pb-14 max-w-[1100px] nf-fade">
    <div class="hidden sm:block">
        <h1 class="font-display text-[38px]">অর্ডার ট্র্যাক করুন</h1>
        <p class="mt-2.5 text-[16.5px] leading-[1.85] text-cocoa max-w-[58ch]">
            অ্যাকাউন্ট খোলার দরকার নেই। অর্ডার নম্বর আর যে মোবাইল নম্বর দিয়ে অর্ডার করেছিলেন, সেটি দিলেই অবস্থা দেখতে পারবেন।
        </p>
    </div>

    <form method="POST" action="{{ route('store.track.lookup') }}" class="mt-4 sm:mt-[22px] bg-panel rounded-[22px] p-[18px] sm:p-[26px]">
        @csrf
        <div class="grid gap-3 sm:gap-3 desk:grid-cols-[1fr_1fr_auto] items-end">
            <div>
                <label class="nf-label" for="tr-order">অর্ডার নম্বর</label>
                <input id="tr-order" name="order_number" value="{{ old('order_number', $prefillOrder) }}" required maxlength="30" placeholder="NFN-1001" class="nf-input uppercase">
            </div>
            <div>
                <label class="nf-label" for="tr-phone">মোবাইল নম্বর</label>
                <input id="tr-phone" name="phone" value="{{ old('phone', $prefillPhone) }}" required inputmode="tel" placeholder="০১৭১২ ৩৪৫ ৬৭৮" class="nf-input">
            </div>
            <button class="bg-espresso text-white rounded-full px-9 h-[52px] text-[15px] font-semibold hover:bg-ink">দেখুন</button>
        </div>
        @error('order_number')<div class="mt-3 text-[13.5px] text-rose">{{ $message }}</div>@enderror
        @error('phone')<div class="mt-2 text-[13.5px] text-rose">{{ $message }}</div>@enderror
        <div class="mt-3.5 text-[13.5px] text-muted">অর্ডার নম্বরটি আপনার কনফার্মেশন এসএমএসে আছে।</div>
    </form>

    @if($notFound)
        <div class="mt-5 bg-rose-soft text-rose rounded-[18px] px-5 py-4 text-[14.5px]">
            এই অর্ডার নম্বর ও মোবাইল নম্বরের সাথে মেলে এমন কোনো অর্ডার পাওয়া যায়নি।
        </div>
    @endif

    @if($order)
        @php $pill = $order->status->pill(); @endphp
        <div class="mt-5 sm:mt-6 grid desk:grid-cols-[1fr_360px] gap-5 desk:gap-6 items-start">
            <div class="bg-panel rounded-[22px] p-[18px] sm:p-[26px]">
                <div class="flex justify-between items-baseline flex-wrap gap-2.5">
                    <div>
                        <div class="text-[18px] sm:text-[19px] font-semibold">{{ bn_digits($order->order_number) }}</div>
                        <div class="mt-[3px] text-[14px] text-muted">অর্ডার করা হয়েছে {{ bn_date($order->created_at) }}</div>
                    </div>
                    <span class="rounded-full px-4 py-2 text-[13.5px] font-semibold" style="background:{{ $pill['bg'] }};color:{{ $pill['color'] }};">{{ $order->status->customerLabel() }}</span>
                </div>

                <div class="mt-6 sm:mt-[26px]">@include('storefront.partials.order-timeline', ['order' => $order])</div>

                @if($order->status !== \App\Enums\OrderStatus::Cancelled)
                    <div class="mt-[22px] bg-white rounded-[16px] p-[18px] text-[14.5px] leading-[1.75] text-bark">
                        @if($order->payment_method === \App\Enums\PaymentMethod::COD)
                            ডেলিভারির সময় <span class="font-semibold">{{ bn_price($order->total_amount) }}</span> ক্যাশে পরিশোধ করতে হবে। রাইডার আসার আগে ফোন করবেন।
                        @else
                            মোট <span class="font-semibold">{{ bn_price($order->total_amount) }}</span> · {{ $order->payment_method->labelBn() }}।
                        @endif
                    </div>
                @endif

                <div class="mt-[18px] flex gap-2.5 flex-wrap">
                    <a href="tel:{{ preg_replace('/\s+/', '', (string) ($order->rider_phone ?: $general['support_phone'])) }}"
                       class="flex-1 min-w-[170px] text-center bg-accent-soft text-accent rounded-full py-[15px] text-[14.5px] font-semibold">
                        {{ $order->rider_phone ? 'রাইডারকে কল করুন' : 'সহায়তায় কল করুন' }}
                    </a>
                    @if($order->status->isCustomerCancellable())
                        <form method="POST" action="{{ route('store.track.cancel', $order->order_number) }}" class="flex-1 min-w-[170px]"
                              x-data @submit="if (! confirm('অর্ডারটি বাতিল করতে চান?')) $event.preventDefault()">
                            @csrf
                            <button class="w-full bg-clay-soft text-espresso rounded-full py-[15px] text-[14.5px] font-semibold">অর্ডার বাতিল করুন</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="bg-panel rounded-[22px] p-[18px] sm:p-[26px]">
                <div class="text-[17px] font-semibold">অর্ডারের পণ্য</div>
                <div class="mt-4">@include('storefront.partials.order-items', ['order' => $order])</div>
                <div class="nf-line my-[18px]"></div>
                <div class="text-[15.5px] font-semibold">ডেলিভারি ঠিকানা</div>
                <div class="mt-2 text-[14.5px] leading-[1.9] text-cocoa">
                    {{ $order->shipping_name }}<br>
                    {{ $order->shipping_address }}<br>
                    {{ collect([$order->shipping_area, $order->shipping_city, bn_digits($order->shipping_postcode)])->filter()->implode(', ') }}<br>
                    {{ bn_phone($order->shipping_phone) }}
                </div>
            </div>
        </div>
    @endif

    {{-- FAQ --}}
    <div class="mt-8 sm:mt-11 bg-panel rounded-[22px] p-[18px] sm:p-[26px]" x-data="{ open: null }">
        <div class="text-[17px] font-semibold">সাধারণ প্রশ্ন</div>
        <div class="mt-3.5 flex flex-col">
            @foreach([
                ['অর্ডার নম্বর হারিয়ে ফেলেছি', 'কনফার্মেশন এসএমএসে অর্ডার নম্বর আছে। না পেলে '.bn_digits($general['support_phone']).' নম্বরে কল করুন, মোবাইল নম্বর দিয়ে খুঁজে দেওয়া হবে।'],
                ['ঠিকানা বদলাতে চাই', 'অর্ডার প্যাক হওয়ার আগে কল করলে ঠিকানা বদলে দেওয়া যায়।'],
                ['ডেলিভারিতে দেরি হচ্ছে', 'উপরের টাইমলাইনে অবস্থা দেখুন। পথে থাকলে রাইডারের নম্বরে সরাসরি কল করতে পারেন।'],
            ] as $i => [$question, $answer])
                @unless($loop->first)<div class="nf-line"></div>@endunless
                <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}" class="py-[15px] flex justify-between items-center gap-3 text-[15px] font-medium text-left">
                    {{ $question }}
                    <span class="text-muted" x-text="open === {{ $i }} ? '−' : '+'">+</span>
                </button>
                <p x-show="open === {{ $i }}" x-collapse x-cloak class="pb-4 text-[14.5px] leading-[1.85] text-cocoa">{{ $answer }}</p>
            @endforeach
        </div>
    </div>
</div>
@endsection
