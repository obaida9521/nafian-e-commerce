@extends('layouts.admin')
@section('title', 'Sale '.$sale->sale_number)

@php $symbol = config('shop.currency_symbol'); @endphp

@section('content')
<div class="max-w-[560px] mx-auto">
    <div class="flex items-center justify-between mb-4 print:hidden">
        <a href="{{ route('admin.pos.index') }}" class="text-[13px] text-gray-500 hover:text-gray-800 flex items-center gap-1.5">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            New sale
        </a>
        <button onclick="window.print()" class="h-9 px-4 rounded-lg border border-[#EADBC4] bg-white text-gray-700 text-[13px] font-semibold hover:border-wine-700">Print receipt</button>
    </div>

    <div class="bg-white border border-[#EADBC4] rounded-xl p-7" id="receipt">
        <div class="text-center mb-5">
            <div class="text-lg font-bold tracking-[0.22em] text-wine-700">{{ config('shop.name', 'NAFIAN') }}</div>
            <div class="text-[12px] text-gray-500 mt-1">Sales receipt</div>
        </div>

        <div class="text-[12.5px] text-gray-600 space-y-1 mb-4 pb-4 border-b border-dashed border-[#EADBC4]">
            <div class="flex justify-between"><span>Receipt</span><span class="font-semibold text-gray-900">{{ $sale->sale_number }}</span></div>
            <div class="flex justify-between"><span>Date</span><span>{{ $sale->created_at->format('M j, Y g:i A') }}</span></div>
            <div class="flex justify-between"><span>Cashier</span><span>{{ $sale->admin?->name ?? '—' }}</span></div>
            @if($sale->customer_name)
                <div class="flex justify-between"><span>Customer</span><span>{{ $sale->customer_name }}</span></div>
            @endif
            @if($sale->customer_phone)
                <div class="flex justify-between"><span>Phone</span><span>{{ $sale->customer_phone }}</span></div>
            @endif
        </div>

        <div class="space-y-2.5 mb-4 pb-4 border-b border-dashed border-[#EADBC4]">
            @foreach($sale->items as $item)
                <div class="flex justify-between text-[13px]">
                    <div class="min-w-0 pr-3">
                        <div class="font-medium text-gray-900 truncate">{{ $item->product_name }}</div>
                        <div class="text-[11.5px] text-gray-500">{{ $item->variant_name }} · {{ $item->quantity }} × {{ $symbol }}{{ number_format($item->unit_price, 0) }}</div>
                    </div>
                    <span class="font-semibold whitespace-nowrap">{{ $symbol }}{{ number_format($item->line_total, 0) }}</span>
                </div>
            @endforeach
        </div>

        <div class="space-y-1.5 text-[13px]">
            <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>{{ $symbol }}{{ number_format($sale->subtotal, 0) }}</span></div>
            @if($sale->discount_amount > 0)
                <div class="flex justify-between text-gray-600"><span>Discount</span><span>−{{ $symbol }}{{ number_format($sale->discount_amount, 0) }}</span></div>
            @endif
            <div class="flex justify-between text-[15px] font-semibold pt-1.5 border-t border-[#F6EAD8]"><span>Total</span><span>{{ $symbol }}{{ number_format($sale->total_amount, 0) }}</span></div>
            <div class="flex justify-between text-gray-600"><span>Cash</span><span>{{ $symbol }}{{ number_format($sale->amount_paid, 0) }}</span></div>
            <div class="flex justify-between text-gray-600"><span>Change</span><span>{{ $symbol }}{{ number_format($sale->change_due, 0) }}</span></div>
        </div>

        <div class="text-center text-[11.5px] text-gray-400 mt-6">Thank you for your purchase</div>
    </div>
</div>
@endsection
