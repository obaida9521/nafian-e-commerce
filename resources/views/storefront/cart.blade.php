@extends('layouts.app')
@section('title', 'আপনার ব্যাগ')
@section('mobile_action_bar', true)
@section('hide_header_search', true)

@section('mobile_header')
    <div class="sm:hidden sticky top-0 z-40 bg-white flex items-center gap-3 px-5 pt-3 pb-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('store.shop') }}" class="text-[17px] font-medium" aria-label="ফিরে যান">←</a>
        <div class="text-[20px] font-semibold">আপনার ব্যাগ</div>
        <span class="ml-auto text-[13.5px] text-muted">{{ bn_digits($items->sum('quantity')) }}টি</span>
    </div>
@endsection

@section('content')
<div class="px-5 sm:px-8 pt-4 sm:pt-[26px] pb-8 nf-fade">
    <div class="hidden sm:block">
        <h1 class="font-display text-[38px]">আপনার ব্যাগ</h1>
        <div class="mt-1.5 text-[15px] text-muted">{{ bn_digits($items->sum('quantity')) }}টি পণ্য</div>
    </div>

    <div id="bag-page" class="sm:pt-6">
        @include('storefront.partials.bag-page', ['items' => $items, 'summary' => $summary])
    </div>
</div>
@endsection
