@extends('layouts.app')
@section('title', 'Track your order')

@section('content')
<div class="max-w-[620px] mx-auto px-6 pt-14 pb-20 nf-fade">
    <h1 class="text-3xl font-semibold tracking-tight mb-2.5 text-center">Track your order</h1>
    <p class="text-[15px] text-gray-500 text-center mb-8">Enter your order number and the email used at checkout.</p>

    <form method="POST" action="{{ route('store.track.lookup') }}" class="bg-white border border-[#EADBC4] rounded-xl p-6 flex flex-col gap-4">
        @csrf
        <div>
            <label class="block text-[13px] text-gray-500 mb-1.5">Order number</label>
            <input name="order_number" value="{{ old('order_number', $prefillOrder ?? '') }}" placeholder="NF-2026-000001" class="w-full h-[44px] border rounded-[7px] px-3.5 font-mono text-sm outline-none focus:border-[#691d2a] {{ $errors->has('order_number') ? 'border-red-400' : 'border-[#EADBC4]' }}">
            @error('order_number')<div class="text-xs text-red-600 mt-1.5">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="block text-[13px] text-gray-500 mb-1.5">Email</label>
            <input name="email" value="{{ old('email') }}" placeholder="you@email.com" class="w-full h-[44px] border rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a] {{ $errors->has('email') ? 'border-red-400' : 'border-[#EADBC4]' }}">
            @error('email')<div class="text-xs text-red-600 mt-1.5">{{ $message }}</div>@enderror
        </div>
        <button class="h-[46px] rounded-lg bg-[#691d2a] hover:bg-[#4d141e] text-white text-sm font-semibold">Track order</button>
    </form>

    @if(!empty($notFound))
        <div class="mt-5 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg px-4 py-3 text-center">No order found with that number and email. Check the details and try again.</div>
    @endif

    @if($order)
        @php
            $st = order_status_style($order->status->value);
            $flow = ['pending'=>'Order placed','confirmed'=>'Confirmed','processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered'];
            $idx = array_search($order->status->value, array_keys($flow), true);
        @endphp
        <div class="mt-7 bg-white border border-[#EADBC4] rounded-xl p-6">
            <div class="flex items-center justify-between mb-5">
                <div class="font-mono text-base font-semibold">{{ $order->order_number }}</div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background:{{ $st['bg'] }};color:{{ $st['color'] }};">{{ $order->status->label() }}</span>
            </div>
            @if($order->status->value === 'cancelled')
                <div class="text-sm text-red-600 mb-4">This order was cancelled.</div>
            @else
                <div class="flex flex-col mb-5">
                    @foreach($flow as $key => $label)
                        @php $done = $idx !== false && array_search($key, array_keys($flow), true) <= $idx; @endphp
                        <div class="flex gap-3 items-center py-1.5">
                            <span class="w-[11px] h-[11px] rounded-full flex-none" style="background:{{ $done ? '#691d2a' : '#E3D8C4' }};"></span>
                            <span class="text-[13.5px] {{ $done ? 'text-gray-900' : 'text-gray-400' }}">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
            <div class="border-t border-[#EFE2CE] pt-4 flex flex-col gap-3">
                @foreach($order->items as $item)
                    <div class="flex justify-between text-sm"><span class="text-gray-600">{{ $item->product_name }} <span class="text-gray-400">· ×{{ $item->quantity }}</span></span><span class="font-semibold">{{ shop_price($item->line_total) }}</span></div>
                @endforeach
                <div class="flex justify-between font-semibold border-t border-[#EFE2CE] pt-3"><span>Total</span><span>{{ shop_price($order->total_amount) }}</span></div>
            </div>
        </div>
    @endif
</div>
@endsection
