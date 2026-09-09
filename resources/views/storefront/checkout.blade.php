@extends('layouts.app')
@section('title', 'Checkout')

@section('content')
<div class="max-w-[1080px] mx-auto px-4 sm:px-6 pt-7 pb-20 nf-fade" x-data="{ pay: '{{ old('payment_method', 'online') }}' }">
    {{-- Steps --}}
    <div class="flex items-center gap-2 mb-8 overflow-x-auto">
        @foreach(['Bag','Details','Payment','Done'] as $i => $step)
            <div class="flex items-center gap-2.5">
                <span class="w-[26px] h-[26px] rounded-full text-xs font-semibold flex items-center justify-center {{ $i <= 1 ? 'bg-[#691d2a] text-white border border-[#691d2a]' : 'border border-[#EADBC4] text-gray-400 bg-white' }}">{{ $i+1 }}</span>
                <span class="text-[13px] font-semibold {{ $i <= 1 ? 'text-gray-900' : 'text-gray-400' }}">{{ $step }}</span>
            </div>
            @if(!$loop->last)<span class="w-[34px] h-px bg-[#EADBC4]"></span>@endif
        @endforeach
    </div>

    @if($errors->any())
        <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">Please correct the highlighted fields.</div>
    @endif

    <form method="POST" action="{{ route('store.checkout.store') }}">
        @csrf
        <div class="grid lg:grid-cols-[1fr_360px] gap-10 items-start">
            {{-- Form --}}
            <div>
                <h2 class="text-lg font-semibold mb-4.5">Contact</h2>
                <div class="mb-4">
                    <label class="block text-[13px] text-gray-500 mb-1.5">Email</label>
                    <input name="email" value="{{ old('email', auth('web')->user()?->email) }}" placeholder="you@email.com" class="w-full h-[42px] border rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a] {{ $errors->has('email') ? 'border-red-400' : 'border-[#EADBC4]' }}">
                    @error('email')<div class="text-xs text-red-600 mt-1.5">{{ $message }}</div>@enderror
                </div>

                <h2 class="text-lg font-semibold mt-6.5 mb-4.5">Shipping address</h2>
                <div class="grid grid-cols-2 gap-3.5">
                    @php
                        $fields = [
                            ['first_name','First name',1],['last_name','Last name',1],
                            ['address','Street address',2],['apt','Apt / suite (optional)',1],
                            ['city','City',1],['zip','ZIP / postcode',1],['phone','Phone',2],
                        ];
                    @endphp
                    @foreach($fields as [$name,$label,$span])
                        <div class="{{ $span === 2 ? 'col-span-2' : '' }}">
                            <label class="block text-[13px] text-gray-500 mb-1.5">{{ $label }}</label>
                            <input name="{{ $name }}" value="{{ old($name) }}" class="w-full h-[42px] border rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a] {{ $errors->has($name) ? 'border-red-400' : 'border-[#EADBC4]' }}">
                            @error($name)<div class="text-xs text-red-600 mt-1.5">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>

                <h2 class="text-lg font-semibold mt-7.5 mb-4">Payment</h2>
                <div class="grid grid-cols-2 gap-3.5">
                    <label class="border rounded-[9px] p-4 cursor-pointer" :style="`border-color:${pay==='online' ? '#691d2a' : '#EADBC4'};background:${pay==='online' ? '#FBF1F2' : '#fff'}`">
                        <input type="radio" name="payment_method" value="online" x-model="pay" class="hidden">
                        <div class="font-semibold text-sm mb-1">Pay online</div>
                        <div class="text-[12.5px] text-gray-500">Card, Apple Pay or wallet</div>
                    </label>
                    <label class="border rounded-[9px] p-4 cursor-pointer" :style="`border-color:${pay==='cod' ? '#691d2a' : '#EADBC4'};background:${pay==='cod' ? '#FBF1F2' : '#fff'}`">
                        <input type="radio" name="payment_method" value="cod" x-model="pay" class="hidden">
                        <div class="font-semibold text-sm mb-1">Cash on delivery</div>
                        <div class="text-[12.5px] text-gray-500">Pay when it arrives</div>
                    </label>
                </div>
            </div>

            {{-- Summary --}}
            <div class="lg:sticky lg:top-[84px] bg-white border border-[#EADBC4] rounded-xl p-5.5">
                <div class="text-base font-semibold mb-4">Order summary</div>
                <div class="flex flex-col gap-3.5 max-h-[240px] overflow-auto mb-4">
                    @foreach($items as $line)
                        @php $v = $line['variant']; $p = $v->product; $img = $p->getFirstMediaUrl('images', 'thumb') ?: $p->getFirstMediaUrl('images'); @endphp
                        <div class="flex gap-3">
                            <div class="w-12 h-[60px] rounded-md flex-none overflow-hidden" style="background:linear-gradient(155deg,{{ $p->tone ?? '#C2BBB0' }},{{ $p->tone2 ?? '#A39B8E' }});">
                                @if($img)<img src="{{ $img }}" alt="{{ $p->name }}" class="w-full h-full object-cover">@endif
                            </div>
                            <div class="flex-1 min-w-0"><div class="text-[13px] font-semibold">{{ $p->name }}</div><div class="text-xs text-gray-400">{{ $v->display_name }} · ×{{ $line['quantity'] }}</div></div>
                            <div class="text-[13px] font-semibold">{{ shop_price($line['line_total']) }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="flex flex-col gap-2 text-sm border-t border-[#EFE2CE] pt-3.5">
                    <div class="flex justify-between text-gray-500"><span>Subtotal</span><span class="text-gray-900">{{ shop_price($summary['subtotal']) }}</span></div>
                    @if($summary['discount'] > 0)<div class="flex justify-between text-green-600"><span>Discount</span><span>−{{ shop_price($summary['discount']) }}</span></div>@endif
                    <div class="flex justify-between text-gray-500"><span>Delivery</span><span class="text-gray-900">{{ $summary['delivery'] > 0 ? shop_price($summary['delivery']) : 'Free' }}</span></div>
                    <div class="flex justify-between font-semibold text-base border-t border-[#EFE2CE] pt-2.5"><span>Total</span><span>{{ shop_price($summary['total']) }}</span></div>
                </div>
                <button class="w-full h-12 mt-4.5 rounded-lg bg-[#691d2a] hover:bg-[#4d141e] text-white text-[15px] font-semibold">Place order</button>
                <div class="text-center text-xs text-gray-400 mt-2.5">Secure checkout · 256-bit encryption</div>
            </div>
        </div>
    </form>
</div>
@endsection
