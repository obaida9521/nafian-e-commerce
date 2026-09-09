@inject('cart', 'App\Services\CartService')
@php
    $lines = $cart->getItems();
    $summary = $cart->getSummary();
@endphp
<div class="flex items-center justify-between px-6 py-5 border-b border-[#EADBC4] flex-none">
    <div class="text-lg font-semibold">Your bag <span class="text-gray-400 font-medium">({{ $cart->getCount() }})</span></div>
    <button @click="cartOpen=false" class="text-gray-500">✕</button>
</div>

@if($lines->isNotEmpty())
    <div class="flex-1 overflow-y-auto px-6 py-2">
        @foreach($lines as $line)
            @php $v = $line['variant']; $p = $v->product; $img = $p->getFirstMediaUrl('images', 'thumb') ?: $p->getFirstMediaUrl('images'); @endphp
            <div class="flex gap-3.5 py-[18px] border-b border-[#EFE2CE]">
                <div class="w-[72px] h-[90px] rounded-lg flex-none overflow-hidden" style="background:linear-gradient(155deg,{{ $p->tone ?? '#C2BBB0' }},{{ $p->tone2 ?? '#A39B8E' }});">
                    @if($img)<img src="{{ $img }}" alt="{{ $p->name }}" class="w-full h-full object-cover">@endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between gap-2">
                        <div class="font-semibold text-sm">{{ $p->name }}</div>
                        <form method="POST" action="{{ route('store.cart.destroy', $v->id) }}" class="js-cart-form">
                            @csrf @method('DELETE')
                            <button class="text-gray-400 hover:text-red-600 text-xs flex-none">✕</button>
                        </form>
                    </div>
                    <div class="text-xs text-gray-400 mt-0.5 mb-3">{{ $v->display_name }}</div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center border border-[#EADBC4] rounded-[7px] overflow-hidden">
                            <form method="POST" action="{{ route('store.cart.update', $v->id) }}" class="js-cart-form">
                                @csrf @method('PATCH')
                                <input type="hidden" name="quantity" value="{{ $line['quantity'] - 1 }}">
                                <button class="w-7 h-7 hover:bg-[#F6EAD8] text-gray-700">−</button>
                            </form>
                            <span class="w-[30px] text-center text-[13px] font-semibold">{{ $line['quantity'] }}</span>
                            <form method="POST" action="{{ route('store.cart.update', $v->id) }}" class="js-cart-form">
                                @csrf @method('PATCH')
                                <input type="hidden" name="quantity" value="{{ $line['quantity'] + 1 }}">
                                <button class="w-7 h-7 hover:bg-[#F6EAD8] text-gray-700">+</button>
                            </form>
                        </div>
                        <span class="font-semibold text-sm">{{ shop_price($line['line_total']) }}</span>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="flex gap-2 pt-[18px] pb-1">
            @if($summary['coupon'])
                <form method="POST" action="{{ route('store.cart.coupon.remove') }}" class="js-cart-form flex-1 flex items-center justify-between bg-[#F0FDF4] border border-[#BBF7D0] rounded-[7px] px-3 h-10">
                    @csrf @method('DELETE')
                    <span class="text-[13px] text-green-700 font-medium">✓ {{ $summary['coupon'] }} applied</span>
                    <button class="text-xs text-gray-500 hover:text-red-600">Remove</button>
                </form>
            @else
                <form method="POST" action="{{ route('store.cart.coupon') }}" class="js-cart-form flex gap-2 w-full">
                    @csrf
                    <input name="code" placeholder="Promo code" class="flex-1 h-10 border border-[#EADBC4] rounded-[7px] px-3 text-sm uppercase outline-none focus:border-[#691d2a]">
                    <button class="h-10 px-[18px] border border-[#691d2a] rounded-[7px] text-[#691d2a] text-[13px] font-semibold hover:bg-[#691d2a] hover:text-white">Apply</button>
                </form>
            @endif
        </div>
    </div>

    <div class="flex-none border-t border-[#EADBC4] px-6 py-5">
        <div class="flex flex-col gap-2.5 text-sm mb-4">
            <div class="flex justify-between text-gray-500"><span>Subtotal</span><span class="text-gray-900">{{ shop_price($summary['subtotal']) }}</span></div>
            @if($summary['discount'] > 0)
                <div class="flex justify-between text-green-600"><span>Discount</span><span>−{{ shop_price($summary['discount']) }}</span></div>
            @endif
            <div class="flex justify-between text-gray-500"><span>Delivery</span><span class="text-gray-900">{{ $summary['delivery'] > 0 ? shop_price($summary['delivery']) : 'Free' }}</span></div>
            <div class="flex justify-between font-semibold text-base border-t border-[#EFE2CE] pt-2.5 mt-0.5"><span>Total</span><span>{{ shop_price($summary['total']) }}</span></div>
        </div>
        <a href="{{ route('store.checkout') }}" class="w-full h-12 rounded-lg bg-[#691d2a] hover:bg-[#4d141e] text-white text-[15px] font-semibold flex items-center justify-center">Checkout · {{ shop_price($summary['total']) }}</a>
    </div>
@else
    <div class="flex-1 flex flex-col items-center justify-center p-10 text-center">
        <div class="w-16 h-16 rounded-full bg-[#F8EAD6] flex items-center justify-center mb-4.5">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#B7AE9F" stroke-width="1.6"><path d="M6 7h12l-1 13H7L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
        </div>
        <div class="font-semibold text-base mb-1.5">Your bag is empty</div>
        <div class="text-sm text-gray-400 mb-5.5">Add something you'll wear for years.</div>
        <a href="{{ route('store.shop') }}" @click="cartOpen=false" class="h-11 px-6 rounded-lg bg-[#691d2a] text-white text-sm font-semibold flex items-center">Start shopping</a>
    </div>
@endif
