@extends('storefront.account.layout')

@section('account')
    @php
        $st = order_status_style($order->status->value);
        $flow = ['pending'=>'Order placed','confirmed'=>'Confirmed','processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered'];
        $order_index = array_search($order->status->value, array_keys($flow), true);
    @endphp
    <a href="{{ route('store.account.orders') }}" class="text-[13px] text-gray-500 mb-4.5 inline-block">← All orders</a>
    <div class="flex items-center gap-3 mb-6">
        <h2 class="text-[22px] font-semibold font-mono">{{ $order->order_number }}</h2>
        <span class="text-xs font-semibold px-2.5 py-1.5 rounded-full" style="background:{{ $st['bg'] }};color:{{ $st['color'] }};">{{ $order->status->label() }}</span>
    </div>

    <div class="grid md:grid-cols-[1fr_280px] gap-6 items-start">
        <div class="bg-white border border-[#EADBC4] rounded-xl p-5.5">
            <div class="flex flex-col gap-4">
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
            <div class="flex justify-between border-t border-[#EFE2CE] mt-4 pt-3.5 font-semibold"><span>Total</span><span>{{ shop_price($order->total_amount) }}</span></div>
        </div>

        <div class="flex flex-col gap-4.5">
            <div class="bg-white border border-[#EADBC4] rounded-xl p-5">
                <div class="text-[13px] font-semibold mb-4">Status</div>
                <div class="flex flex-col">
                    @foreach($flow as $key => $label)
                        @php $done = $order_index !== false && array_search($key, array_keys($flow), true) <= $order_index && $order->status->value !== 'cancelled'; @endphp
                        <div class="flex gap-3 items-center py-1.5">
                            <span class="w-[11px] h-[11px] rounded-full flex-none" style="background:{{ $done ? '#691d2a' : '#E3D8C4' }};"></span>
                            <span class="text-[13.5px]" style="color:{{ $done ? '#111' : '#9CA3AF' }};">{{ $label }}</span>
                        </div>
                    @endforeach
                    @if($order->status->value === 'cancelled')
                        <div class="flex gap-3 items-center py-1.5"><span class="w-[11px] h-[11px] rounded-full bg-red-500 flex-none"></span><span class="text-[13.5px] text-red-600">Cancelled</span></div>
                    @endif
                </div>
            </div>
            <div class="bg-white border border-[#EADBC4] rounded-xl p-5">
                <div class="text-[13px] font-semibold mb-2">Shipping to</div>
                <div class="text-[13px] text-gray-500 leading-relaxed">{{ $order->shipping_name }}<br>{{ $order->shipping_address }}<br>{{ $order->shipping_city }}, {{ $order->shipping_district }}</div>
            </div>
        </div>
    </div>
@endsection
