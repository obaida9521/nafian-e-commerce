@extends('layouts.admin')
@section('title', 'Order detail')

@php
    $symbol = config('shop.currency_symbol');
    $st = order_status_style($order->status->value);
@endphp

@section('content')
<div class="max-w-[1280px] mx-auto">
    <a href="{{ route('admin.orders.index') }}" class="text-[13px] text-gray-500 mb-3.5 inline-block">← All orders</a>

    <div class="flex flex-wrap items-center justify-between gap-4 mb-4.5">
        <div class="flex items-center gap-3">
            <h2 class="text-2xl font-semibold font-mono">{{ $order->order_number }}</h2>
            <span class="text-xs font-semibold px-2.5 py-1.5 rounded-full" style="background:{{ $st['bg'] }};color:{{ $st['color'] }};">{{ $order->status->label() }}</span>
        </div>
        <div class="flex items-center gap-2.5">
            <button onclick="window.print()" class="h-[38px] px-4 border border-[#EADBC4] rounded-lg bg-white text-gray-700 text-[13px] font-semibold hover:border-wine-700">Print invoice</button>
        </div>
    </div>

    {{-- Fulfilment flow --}}
    <div class="bg-white border border-[#EADBC4] rounded-xl p-6 mb-4.5">
        <div class="flex items-start mb-5">
            @foreach($flow as $i => $step)
                <div class="flex flex-col items-center flex-1 text-center">
                    <div class="flex items-center w-full">
                        <span class="h-0.5 flex-1 {{ $i === 0 ? 'bg-transparent' : ($step['state'] === 'done' || $step['state'] === 'current' ? 'bg-wine-700' : 'bg-[#EADBC4]') }}"></span>
                        @php
                            $circle = match($step['state']) {
                                'done' => 'bg-wine-700 border-wine-700 text-white',
                                'current' => 'bg-white border-wine-700 text-wine-700 ring-4 ring-wine-700/10',
                                'halted' => 'bg-gray-100 border-gray-200 text-gray-400',
                                default => 'bg-white border-[#EADBC4] text-gray-400',
                            };
                        @endphp
                        <span class="w-[34px] h-[34px] rounded-full flex-none grid place-items-center border-2 text-[13px] font-bold {{ $circle }}">
                            @if($step['state'] === 'done')
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6 9 17l-5-5"/></svg>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>
                        <span class="h-0.5 flex-1 {{ $i === count($flow) - 1 ? 'bg-transparent' : ($step['state'] === 'done' ? 'bg-wine-700' : 'bg-[#EADBC4]') }}"></span>
                    </div>
                    <div class="text-[13px] mt-2.5 {{ $step['state'] === 'current' ? 'font-semibold text-gray-900' : 'text-gray-500' }}">{{ $step['label'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between gap-4 border-t border-[#F6EAD8] pt-4.5 flex-wrap">
            <div class="text-[13.5px] text-gray-500 flex items-center gap-2.5">
                @if($order->status->isTerminal() && in_array($order->status->value, ['cancelled','refunded']))
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background:{{ $st['bg'] }};color:{{ $st['color'] }};">{{ $order->status->label() }}</span>
                @endif
                <span>
                    @switch($order->status->value)
                        @case('delivered') Order completed and delivered. @break
                        @case('cancelled') Order was cancelled. @break
                        @case('refunded') Order was refunded. @break
                        @default Currently {{ strtolower($order->status->label()) }} — move it to the next stage when ready.
                    @endswitch
                </span>
            </div>
            <div class="flex items-center gap-2.5" x-data="{ cancelOpen: false }">
                @if($cancellable)
                    <button @click="cancelOpen = true" class="h-[42px] px-4.5 border border-red-300 rounded-lg bg-white text-red-600 text-sm font-semibold hover:bg-red-50">Cancel order</button>
                @endif

                @if($primaryAction && $primaryAction->value === 'refunded')
                    <form method="POST" action="{{ route('admin.orders.update-status', $order) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="refunded">
                        <button class="h-[42px] px-4.5 border border-[#EADBC4] rounded-lg bg-white text-gray-700 text-sm font-semibold hover:border-wine-700">Issue refund</button>
                    </form>
                @elseif($primaryAction)
                    <form method="POST" action="{{ route('admin.orders.update-status', $order) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $primaryAction->value }}">
                        <button class="h-[42px] px-6 rounded-lg bg-wine-700 hover:bg-[#4d141e] text-white text-sm font-semibold flex items-center gap-2">
                            Mark as {{ $primaryAction->label() }}
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </button>
                    </form>
                @endif

                {{-- Cancel modal --}}
                <div x-show="cancelOpen" x-cloak class="fixed inset-0 z-[75] flex items-center justify-center">
                    <div @click="cancelOpen = false" class="absolute inset-0 bg-[#3c1018]/40"></div>
                    <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="relative bg-white rounded-[14px] w-[440px] max-w-[92vw] p-6.5 shadow-2xl">
                        @csrf
                        <div class="text-lg font-semibold mb-1">Cancel order</div>
                        <div class="text-[13px] text-gray-500 mb-4">Stock will be released. This cannot be undone.</div>
                        <textarea name="cancelled_reason" rows="3" required placeholder="Reason for cancellation…" class="w-full border border-[#EADBC4] rounded-[7px] px-3.5 py-2.5 text-sm outline-none focus:border-wine-700 mb-4"></textarea>
                        <div class="flex justify-end gap-2.5">
                            <button type="button" @click="cancelOpen = false" class="h-[42px] px-5 border border-[#EADBC4] rounded-lg bg-white text-gray-700 text-sm font-semibold">Keep order</button>
                            <button class="h-[42px] px-5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold">Confirm cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-[1.6fr_1fr] gap-4.5 items-start">
        {{-- Items --}}
        <div class="bg-white border border-[#EADBC4] rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-[#EFE2CE] text-[15px] font-semibold">Items</div>
            @foreach($order->items as $item)
                @php $itemImg = $item->variant?->product?->getFirstMediaUrl('images', 'thumb') ?: $item->variant?->product?->getFirstMediaUrl('images'); @endphp
                <div class="flex gap-3 items-center px-5 py-3.5 border-b border-[#F6EAD8]">
                    <div class="w-[46px] h-[56px] rounded-[7px] flex-none overflow-hidden" style="background:linear-gradient(155deg,{{ $item->variant?->product?->tone ?? '#C9B49A' }},{{ $item->variant?->product?->tone2 ?? '#A98F6E' }});">
                        @if($itemImg)<img src="{{ $itemImg }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">@endif
                    </div>
                    <div class="flex-1"><div class="text-sm font-semibold">{{ $item->product_name }}</div><div class="text-xs text-gray-400">{{ $item->variant_name }} · <span class="font-mono">{{ $item->sku }}</span></div></div>
                    <span class="text-[13px] text-gray-500">{{ $symbol }}{{ number_format($item->unit_price, 0) }} × {{ $item->quantity }}</span>
                    <span class="text-sm font-semibold w-16 text-right">{{ $symbol }}{{ number_format($item->line_total, 0) }}</span>
                </div>
            @endforeach
            <div class="px-5 py-4 flex flex-col gap-2 text-sm">
                <div class="flex justify-between text-gray-500"><span>Subtotal</span><span class="text-gray-900">{{ $symbol }}{{ number_format($order->subtotal, 0) }}</span></div>
                @if($order->discount_amount > 0)
                    <div class="flex justify-between text-green-600"><span>Discount {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}</span><span>−{{ $symbol }}{{ number_format($order->discount_amount, 0) }}</span></div>
                @endif
                <div class="flex justify-between text-gray-500"><span>Delivery</span><span class="text-gray-900">{{ $symbol }}{{ number_format($order->delivery_charge, 0) }}</span></div>
                <div class="flex justify-between font-semibold text-base border-t border-[#EFE2CE] pt-2.5"><span>Total</span><span>{{ $symbol }}{{ number_format($order->total_amount, 0) }}</span></div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="flex flex-col gap-4">
            <div class="bg-white border border-[#EADBC4] rounded-xl p-4.5"><div class="text-[13px] font-semibold mb-2.5">Customer</div><div class="text-sm font-semibold">{{ $order->user?->name ?? $order->shipping_name }}</div><div class="text-[13px] text-gray-500">{{ $order->user?->email ?? $order->guest_email }}</div></div>
            <div class="bg-white border border-[#EADBC4] rounded-xl p-4.5"><div class="text-[13px] font-semibold mb-2.5">Shipping address</div><div class="text-[13px] text-gray-500 leading-relaxed">{{ $order->shipping_name }}<br>{{ $order->shipping_phone }}<br>{{ $order->shipping_address }}<br>{{ $order->shipping_city }}, {{ $order->shipping_district }}</div></div>
            <div class="bg-white border border-[#EADBC4] rounded-xl p-4.5">
                <div class="text-[13px] font-semibold mb-2.5">Payment</div>
                <div class="text-sm">{{ $order->payment_method->label() }}</div>
                @foreach($order->payments as $payment)
                    <div class="flex items-center justify-between text-[13px] text-gray-500 mt-2">
                        <span>{{ $symbol }}{{ number_format($payment->amount, 0) }}</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full" style="background:{{ order_status_style($payment->status->value)['bg'] }};color:{{ order_status_style($payment->status->value)['color'] }};">{{ $payment->status->label() }}</span>
                    </div>
                @endforeach
            </div>
            <div class="bg-white border border-[#EADBC4] rounded-xl p-4.5">
                <div class="text-[13px] font-semibold mb-3.5">Timeline</div>
                @foreach($timeline as $t)
                    <div class="flex gap-3 items-center py-1.5">
                        <span class="w-2.5 h-2.5 rounded-full flex-none" style="background:{{ $t['done'] ? '#691d2a' : '#E3D8C4' }};"></span>
                        <span class="text-[13px] {{ $t['done'] ? 'text-gray-900' : 'text-gray-400' }}">{{ $t['label'] }}</span>
                        @if($t['at'])<span class="text-xs text-gray-400 ml-auto">{{ $t['at'] }}</span>@endif
                    </div>
                @endforeach
            </div>
            @if($order->cancelled_reason)
                <div class="bg-red-50 border border-red-200 rounded-xl p-4.5 text-sm"><div class="font-semibold text-red-800 mb-1">Cancellation reason</div><div class="text-red-700">{{ $order->cancelled_reason }}</div></div>
            @endif
        </div>
    </div>
</div>
@endsection
