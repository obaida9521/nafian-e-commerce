@extends('layouts.app')
@section('title', $product->name.' — রিভিউ')
@section('hide_header_search', true)

@php
    /** @var \App\Models\Product $product */
    $baseUrl = route('store.product.reviews', $product->slug);
    $chip = 'flex-none rounded-full px-4 py-2.5 text-[13.5px] font-medium whitespace-nowrap';
@endphp

@section('mobile_header')
    <div class="sm:hidden sticky top-0 z-40 bg-white flex items-center gap-3 px-5 pt-3 pb-3">
        <a href="{{ route('store.product', $product->slug) }}" class="text-[17px] font-medium" aria-label="ফিরে যান">←</a>
        <div class="min-w-0">
            <div class="text-[19px] font-semibold leading-tight">রিভিউ</div>
            <div class="text-[12.5px] text-muted truncate">{{ $product->name }}</div>
        </div>
    </div>
@endsection

@section('content')
<div class="px-5 sm:px-8 pt-2 sm:pt-[34px] pb-10 max-w-[1100px] mx-auto nf-fade">
    <div class="hidden sm:block text-[14px] text-muted">
        <a href="{{ route('store.home') }}" class="hover:text-accent">হোম</a><span class="mx-1.5">/</span>
        <a href="{{ route('store.product', $product->slug) }}" class="hover:text-accent">{{ $product->name }}</a><span class="mx-1.5">/</span>
        <span class="text-ink">রিভিউ</span>
    </div>
    <h1 class="hidden sm:block mt-3 font-display text-[40px]">{{ $product->name }} — রিভিউ</h1>

    <div class="mt-3 sm:mt-7 grid desk:grid-cols-[320px_1fr] gap-5 desk:gap-12 items-start">
        <div class="flex flex-col gap-4 desk:sticky desk:top-24">
            @include('storefront.partials.review-summary')
            <a href="#write-review" class="hidden desk:block text-center bg-sand-3 text-espresso rounded-full py-3.5 text-[14.5px] font-semibold hover:bg-sand-2">
                {{ $userReview ? 'আপনার রিভিউ সম্পাদনা করুন' : 'রিভিউ লিখুন' }}
            </a>
        </div>

        <div class="min-w-0">
            {{-- Star filter --}}
            <div class="nf-rail gap-2 pb-1" aria-label="রেটিং অনুযায়ী দেখুন">
                <a href="{{ $baseUrl }}" @class([$chip, 'bg-espresso text-white' => ! $star, 'bg-sand-3 text-ink' => $star])>সব · {{ bn_digits($totalReviews) }}</a>
                @foreach($ratingBreakdown as $rating => $count)
                    @if($count > 0 || $star === $rating)
                        <a href="{{ $baseUrl }}?rating={{ $rating }}" @class([$chip, 'bg-espresso text-white' => $star === $rating, 'bg-sand-3 text-ink' => $star !== $rating])>
                            {{ bn_digits($rating) }} ★ · {{ bn_digits($count) }}
                        </a>
                    @endif
                @endforeach
            </div>

            <div class="mt-4">
                @include('storefront.partials.review-list', [
                    'reviews' => $reviews->getCollection(),
                    'empty' => $star ? bn_digits($star).' স্টারের কোনো রিভিউ নেই।' : null,
                ])
            </div>

            {{ $reviews->onEachSide(1)->links('storefront.partials.pagination') }}

            <div class="mt-6">
                @include('storefront.partials.review-form', ['from' => 'reviews'])
            </div>

            <a href="{{ route('store.product', $product->slug) }}" class="mt-5 block text-center text-[14.5px] font-medium text-accent">← পণ্যের পেজে ফিরুন</a>
        </div>
    </div>
</div>
@endsection
