@extends('storefront.account.layout')

@section('account')
    @if($orders->isEmpty())
        <div class="bg-white border border-[#EADBC4] rounded-xl p-12 text-center">
            <div class="text-gray-400 mb-4">You haven't placed any orders yet.</div>
            <a href="{{ route('store.shop') }}" class="h-11 px-6 inline-flex items-center rounded-lg bg-[#691d2a] text-white text-sm font-semibold">Start shopping</a>
        </div>
    @else
        <div class="bg-white border border-[#EADBC4] rounded-xl overflow-hidden">
            <div class="grid grid-cols-[1.4fr_1fr_0.8fr_1fr_60px] px-5 py-3 text-[11px] tracking-wide uppercase text-gray-400 font-semibold border-b border-[#EFE2CE]">
                <span>Order</span><span>Date</span><span>Items</span><span>Total</span><span></span>
            </div>
            @foreach($orders as $o)
                @php $st = order_status_style($o->status->value); @endphp
                <a href="{{ route('store.account.orders.show', $o->order_number) }}" class="grid grid-cols-[1.4fr_1fr_0.8fr_1fr_60px] px-5 py-3.5 items-center border-b border-[#F6EAD8] hover:bg-[#FBEFDD]">
                    <div class="flex flex-col gap-1.5">
                        <span class="font-mono text-[13px]">{{ $o->order_number }}</span>
                        <span class="text-[11px] font-semibold px-2 py-[3px] rounded-full w-fit" style="background:{{ $st['bg'] }};color:{{ $st['color'] }};">{{ $o->status->label() }}</span>
                    </div>
                    <span class="text-[13px] text-gray-500">{{ $o->created_at->format('M j, Y') }}</span>
                    <span class="text-[13px] text-gray-500">{{ $o->items_count }}</span>
                    <span class="text-sm font-semibold">{{ shop_price($o->total_amount) }}</span>
                    <span class="text-[13px] text-[#691d2a] font-semibold">View</span>
                </a>
            @endforeach
        </div>
    @endif
@endsection
