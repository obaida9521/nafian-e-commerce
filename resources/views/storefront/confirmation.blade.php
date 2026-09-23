@extends('layouts.app')
@section('title', 'অর্ডার নিশ্চিত হয়েছে')
@section('hide_header_search', true)

@php
    $firstName = \Illuminate\Support\Str::of($order->shipping_name)->trim()->explode(' ')->first();
@endphp

@section('content')
<div class="px-5 sm:px-8 pt-6 sm:pt-14 pb-14 nf-fade">
    <div class="max-w-[760px] mx-auto text-center">
        <div class="w-[62px] h-[62px] sm:w-16 sm:h-16 rounded-full bg-accent-soft text-accent grid place-items-center mx-auto text-[26px] sm:text-[28px]">✓</div>
        <h1 class="mt-[18px] sm:mt-[22px] font-display text-[30px] sm:text-[38px]">ধন্যবাদ, {{ $firstName }}</h1>
        <p class="mt-2.5 text-[15px] sm:text-[16.5px] leading-[1.85] text-cocoa">
            আপনার অর্ডার আমরা পেয়েছি। কিছুক্ষণের মধ্যে আমাদের টিম ফোনে কল করে নিশ্চিত করবে।
        </p>
        <div class="mt-4 sm:mt-[26px] inline-flex gap-2.5 flex-wrap justify-center">
            <span class="bg-sand rounded-full px-5 py-2.5 sm:py-[11px] text-[14px] sm:text-[14.5px] font-semibold">অর্ডার নম্বর · {{ bn_digits($order->order_number) }}</span>
            <span class="bg-sand rounded-full px-5 py-2.5 sm:py-[11px] text-[14px] sm:text-[14.5px] font-medium">{{ $order->payment_method->labelBn() }}</span>
        </div>
    </div>

    {{-- Phone: live status, amount due, support --}}
    <div class="sm:hidden mt-6 flex flex-col gap-3.5">
        <div class="bg-panel rounded-[18px] p-[18px]">
            <div class="text-[15.5px] font-semibold">অর্ডারের অবস্থা</div>
            <div class="mt-4">@include('storefront.partials.order-timeline', ['order' => $order, 'dense' => true])</div>
        </div>

        <div class="bg-panel rounded-[18px] p-[18px] flex justify-between items-center gap-3">
            <div>
                <div class="text-[15px] font-semibold">{{ $order->payment_method === \App\Enums\PaymentMethod::COD ? 'ডেলিভারিতে দিতে হবে' : 'পরিশোধযোগ্য' }}</div>
                <div class="mt-0.5 text-[12.5px] text-muted">{{ $order->payment_method->labelBn() }}</div>
            </div>
            <div class="text-[19px] font-semibold text-espresso">{{ bn_price($order->total_amount) }}</div>
        </div>

        <div class="bg-panel rounded-[18px] p-[18px]">
            <div class="text-[15.5px] font-semibold">অর্ডারের পণ্য</div>
            <div class="mt-4">@include('storefront.partials.order-items', ['order' => $order])</div>
        </div>

        <a href="tel:{{ preg_replace('/\s+/', '', (string) $general['support_phone']) }}" class="bg-accent-soft text-accent rounded-full py-[15px] text-center text-[15px] font-semibold">সহায়তায় কল করুন</a>
        <a href="{{ route('store.track', ['order' => $order->order_number]) }}" class="bg-espresso text-white rounded-full py-[15px] text-center text-[15px] font-semibold">অর্ডার ট্র্যাক করুন</a>
    </div>

    {{-- Desktop: items + delivery --}}
    <div class="hidden sm:grid max-w-[900px] mx-auto mt-[34px] grid-cols-1 desk:grid-cols-2 gap-6">
        <div class="bg-panel rounded-[22px] p-[26px]">
            <div class="text-[17px] font-semibold">অর্ডারের পণ্য</div>
            <div class="mt-4">@include('storefront.partials.order-items', ['order' => $order])</div>
        </div>

        <div class="bg-panel rounded-[22px] p-[26px]">
            <div class="text-[17px] font-semibold">ডেলিভারি</div>
            <div class="mt-3.5 text-[15px] leading-[1.9] text-cocoa">
                {{ $order->shipping_name }}<br>
                {{ $order->shipping_address }}<br>
                {{ collect([$order->shipping_area, $order->shipping_city, bn_digits($order->shipping_postcode)])->filter()->implode(', ') }}<br>
                {{ bn_phone($order->shipping_phone) }}
            </div>
            <div class="nf-line my-[18px]"></div>
            <div class="flex justify-between text-[15px] text-cocoa"><span>সম্ভাব্য ডেলিভারি</span><span class="text-ink">{{ bn_date($order->estimatedDeliveryDate()) }}</span></div>
            <div class="mt-5 flex gap-2.5 flex-wrap">
                <a href="{{ route('store.track', ['order' => $order->order_number]) }}" class="flex-1 min-w-[150px] text-center bg-espresso text-white rounded-full py-[15px] text-[14.5px] font-semibold hover:bg-ink">অর্ডার ট্র্যাক করুন</a>
                <a href="{{ route('store.shop') }}" class="flex-1 min-w-[150px] text-center bg-accent-soft text-accent rounded-full py-[15px] text-[14.5px] font-semibold">কেনাকাটা চালিয়ে যান</a>
            </div>
        </div>
    </div>
</div>
@endsection
