@extends('layouts.app')
@section('title', 'Order confirmed')

@section('content')
<div class="max-w-[620px] mx-auto px-6 pt-14 pb-20 text-center nf-fade">
    <div class="w-[76px] h-[76px] rounded-full bg-[#DCFCE7] flex items-center justify-center mx-auto mb-6">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
    </div>
    <h1 class="text-3xl font-semibold tracking-tight mb-2.5">Thank you for your order</h1>
    <p class="text-[15px] text-gray-500 mb-1.5">A confirmation has been sent to {{ $email ?? 'your email' }}.</p>
    <div class="font-mono text-sm text-[#691d2a] bg-[#F8EAD6] inline-block px-3.5 py-[7px] rounded-[7px] my-4">Order {{ $order->order_number }}</div>

    <div class="bg-white border border-[#EADBC4] rounded-xl p-5.5 text-left mb-6">
        <div class="flex flex-col gap-3.5">
            @foreach($order->items as $item)
                @php $p = $item->variant?->product; $img = $p?->getFirstMediaUrl('images', 'thumb') ?: $p?->getFirstMediaUrl('images'); @endphp
                <div class="flex gap-3.5 items-center">
                    <div class="w-[50px] h-[62px] rounded-[7px] flex-none overflow-hidden" style="background:linear-gradient(155deg,{{ $p?->tone ?? '#C9B49A' }},{{ $p?->tone2 ?? '#A98F6E' }});">
                        @if($img)<img src="{{ $img }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">@endif
                    </div>
                    <div class="flex-1"><div class="text-sm font-semibold">{{ $item->product_name }}</div><div class="text-xs text-gray-400">{{ $item->variant_name }} · ×{{ $item->quantity }}</div></div>
                    <div class="text-sm font-semibold">{{ shop_price($item->line_total) }}</div>
                </div>
            @endforeach
        </div>
        <div class="flex justify-between border-t border-[#EFE2CE] mt-4 pt-3.5 font-semibold text-base"><span>Total paid</span><span>{{ shop_price($order->total_amount) }}</span></div>
    </div>

    <div class="flex items-center justify-center gap-2 text-sm text-gray-500 mb-7.5">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#D4A853" stroke-width="1.7"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/></svg>
        Estimated delivery <strong class="text-gray-900 font-semibold">{{ estimated_delivery() }}</strong>
    </div>
    <div class="flex gap-3 justify-center">
        <a href="{{ auth('web')->check() ? route('store.account.orders.show', $order->order_number) : route('store.track', ['order' => $order->order_number]) }}" class="h-[46px] px-6 rounded-lg border border-[#691d2a] text-[#691d2a] hover:bg-[#691d2a] hover:text-white text-sm font-semibold flex items-center">Track order</a>
        <a href="{{ route('store.shop') }}" class="h-[46px] px-6 rounded-lg bg-[#691d2a] hover:bg-[#4d141e] text-white text-sm font-semibold flex items-center">Continue shopping</a>
    </div>
</div>
@endsection
