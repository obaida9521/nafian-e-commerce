@php
    /** @var \App\Models\Order $order */
    $showTotalsOnly = $showTotalsOnly ?? false;
@endphp
<div class="flex flex-col gap-3.5">
    @foreach($order->items as $item)
        <div class="flex gap-3 items-center">
            <x-ui.product-image :product="$item->variant?->product" :variant="$item->variant" class="w-[52px] h-[52px] rounded-[10px] flex-none" />
            <div class="flex-1 min-w-0">
                <div class="text-[15px] font-semibold truncate">{{ $item->product_name }}</div>
                <div class="text-[13.5px] text-muted">{{ bn_digits($item->variant_name) }} · {{ bn_digits($item->quantity) }}টি</div>
            </div>
            <div class="text-[15px] font-semibold">{{ bn_price($item->line_total) }}</div>
        </div>
    @endforeach
</div>

<div class="nf-line my-[18px]"></div>
<div class="flex flex-col gap-2.5 text-[14.5px] sm:text-[15px] text-cocoa">
    <div class="flex justify-between"><span>সাবটোটাল</span><span class="text-ink">{{ bn_price($order->subtotal) }}</span></div>
    @if($order->discount_amount > 0)
        <div class="flex justify-between"><span>ছাড়@if($order->coupon_code) ({{ $order->coupon_code }})@endif</span><span class="text-accent">−{{ bn_price($order->discount_amount) }}</span></div>
    @endif
    <div class="flex justify-between"><span>ডেলিভারি</span><span class="text-ink">{{ $order->delivery_charge > 0 ? bn_price($order->delivery_charge) : 'ফ্রি' }}</span></div>
</div>
<div class="nf-line my-[18px]"></div>
<div class="flex justify-between items-baseline">
    <span class="text-[16px] font-semibold">সর্বমোট{{ $order->payment_method === \App\Enums\PaymentMethod::COD ? ' (COD)' : '' }}</span>
    <span class="text-[20px] sm:text-[22px] font-semibold text-espresso">{{ bn_price($order->total_amount) }}</span>
</div>
