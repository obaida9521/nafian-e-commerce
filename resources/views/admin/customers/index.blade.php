@extends('layouts.admin')
@section('title', 'গ্রাহক')

@php $symbol = config('shop.currency_symbol'); @endphp

@section('content')
<div>
    {{-- Tabs --}}
    <div class="flex items-center gap-1 mb-4">
        <a href="{{ route('admin.customers.index', ['tab' => 'registered'] + request()->only('search')) }}"
           class="h-[34px] px-4 rounded-lg text-sm font-semibold grid place-items-center {{ $tab === 'registered' ? 'bg-wine-700 text-white' : 'bg-white border border-[#E9E4E0] text-gray-600 hover:border-wine-700' }}">
            Registered <span class="opacity-70">({{ $registeredCount }})</span>
        </a>
        <a href="{{ route('admin.customers.index', ['tab' => 'guests'] + request()->only('search')) }}"
           class="h-[34px] px-4 rounded-lg text-sm font-semibold grid place-items-center {{ $tab === 'guests' ? 'bg-wine-700 text-white' : 'bg-white border border-[#E9E4E0] text-gray-600 hover:border-wine-700' }}">
            Guests <span class="opacity-70">({{ $guestCount }})</span>
        </a>
    </div>

    <form method="GET" class="flex gap-2.5 mb-4.5">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input name="search" value="{{ request('search') }}" placeholder="Name, email or phone…" class="h-[38px] w-72 border border-[#E9E4E0] rounded-lg px-3 text-sm bg-white outline-none focus:border-wine-700">
        <button class="inline-flex items-center gap-1.5 h-[38px] px-4 rounded-lg bg-wine-700 hover:bg-[#2A2220] text-white text-sm font-semibold"><x-ui.icon name="search" :size="15" />Search</button>
    </form>

    @if($tab === 'guests')
        <div class="bg-white border border-[#E9E4E0] rounded-xl overflow-hidden">
            <div class="grid grid-cols-[1.6fr_1.6fr_1.2fr_0.8fr_1fr_1fr] px-5 py-3 text-[11px] tracking-wide uppercase text-gray-400 font-semibold border-b border-[#F0ECE9]">
                <span>Guest</span><span>Email</span><span>Phone</span><span>Orders</span><span>Spent</span><span>First order</span>
            </div>
            @forelse($guests as $g)
                <a href="{{ route('admin.customers.index', ['tab' => 'guests', 'guest' => $g->guest_email] + request()->only('search')) }}" class="grid grid-cols-[1.6fr_1.6fr_1.2fr_0.8fr_1fr_1fr] px-5 py-3 items-center border-b border-[#F5F2F0] text-[13.5px] hover:bg-[#FBEFDD]">
                    <span class="flex items-center gap-2.5">
                        <span class="w-[34px] h-[34px] rounded-full bg-[#F1E4D0] text-gray-500 grid place-items-center font-semibold flex-none">{{ strtoupper(substr($g->name ?? '?', 0, 1)) }}</span>
                        <span class="font-semibold truncate">{{ $g->name ?? '—' }} <span class="text-[10px] font-semibold text-gray-400 align-middle">GUEST</span></span>
                    </span>
                    <span class="text-gray-500 truncate">{{ $g->guest_email }}</span>
                    <span class="text-gray-500 font-mono text-xs">{{ $g->phone ?? '—' }}</span>
                    <span class="text-gray-700">{{ $g->orders_count }}</span>
                    <span class="font-semibold">{{ $symbol }}{{ number_format($g->orders_total ?? 0, 0) }}</span>
                    <span class="text-gray-500">{{ \Illuminate\Support\Carbon::parse($g->first_order_at)->format('M Y') }}</span>
                </a>
            @empty
                <div class="px-5 py-12 text-center text-gray-400">No guest orders yet.</div>
            @endforelse
        </div>
        <div class="mt-4">{{ $guests->links() }}</div>
    @else
        <div class="bg-white border border-[#E9E4E0] rounded-xl overflow-hidden">
            <div class="grid grid-cols-[1.6fr_1.6fr_1.2fr_0.8fr_1fr_1fr_0.9fr] px-5 py-3 text-[11px] tracking-wide uppercase text-gray-400 font-semibold border-b border-[#F0ECE9]">
                <span>Customer</span><span>Email</span><span>Phone</span><span>Orders</span><span>Spent</span><span>Joined</span><span>Status</span>
            </div>
            @forelse($customers as $c)
                <a href="{{ route('admin.customers.index', ['view' => $c->id] + request()->only('search')) }}" class="grid grid-cols-[1.6fr_1.6fr_1.2fr_0.8fr_1fr_1fr_0.9fr] px-5 py-3 items-center border-b border-[#F5F2F0] text-[13.5px] hover:bg-[#FBEFDD]">
                    <span class="flex items-center gap-2.5">
                        <span class="w-[34px] h-[34px] rounded-full bg-[#F3EFEC] text-wine-700 grid place-items-center font-semibold flex-none">{{ strtoupper(substr($c->name, 0, 1)) }}</span>
                        <span class="font-semibold truncate">{{ $c->name }}</span>
                    </span>
                    <span class="text-gray-500 truncate">{{ $c->email }}</span>
                    <span class="text-gray-500 font-mono text-xs">{{ $c->phone ?? '—' }}</span>
                    <span class="text-gray-700">{{ $c->orders_count }}</span>
                    <span class="font-semibold">{{ $symbol }}{{ number_format($c->orders_total ?? 0, 0) }}</span>
                    <span class="text-gray-500">{{ $c->created_at?->format('M Y') }}</span>
                    <span><span class="text-[11px] font-semibold px-2.5 py-[3px] rounded-full" style="background:{{ $c->is_active ? '#DCFCE7' : '#F3F4F6' }};color:{{ $c->is_active ? '#166534' : '#374151' }};">{{ $c->is_active ? 'Active' : 'Suspended' }}</span></span>
                </a>
            @empty
                <div class="px-5 py-12 text-center text-gray-400">No customers yet.</div>
            @endforelse
        </div>
        <div class="mt-4">{{ $customers->links() }}</div>
    @endif
</div>

{{-- Slide-over drawer: registered customer --}}
@if($selected)
    <div x-data="{ open: true }" x-init="$nextTick(() => open = true)">
        <div x-show="open" x-cloak class="fixed inset-0 z-[75]">
            <a href="{{ route('admin.customers.index', request()->only('search')) }}" class="absolute inset-0 bg-[#1A1413]/40 block"></a>
            <div class="absolute top-0 right-0 h-full w-[440px] max-w-[92vw] bg-white shadow-2xl flex flex-col"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
                <div class="flex items-center justify-between px-6 py-5 border-b border-[#E9E4E0]">
                    <div class="flex items-center gap-3">
                        <span class="w-11 h-11 rounded-full bg-[#F3EFEC] text-wine-700 grid place-items-center font-semibold text-[17px]">{{ strtoupper(substr($selected->name, 0, 1)) }}</span>
                        <div>
                            <div class="text-base font-semibold">{{ $selected->name }}</div>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:{{ $selected->is_active ? '#DCFCE7' : '#F3F4F6' }};color:{{ $selected->is_active ? '#166534' : '#374151' }};">{{ $selected->is_active ? 'Active' : 'Suspended' }}</span>
                        </div>
                    </div>
                    <a href="{{ route('admin.customers.index', request()->only('search')) }}" class="text-gray-500">✕</a>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-5.5">
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <div class="bg-[#FBEFDD] rounded-[10px] p-3.5"><div class="text-xs text-gray-400 mb-1">Total spent</div><div class="text-xl font-semibold">{{ $symbol }}{{ number_format($selected->orders_total ?? 0, 0) }}</div></div>
                        <div class="bg-[#FBEFDD] rounded-[10px] p-3.5"><div class="text-xs text-gray-400 mb-1">Orders</div><div class="text-xl font-semibold">{{ $selected->orders_count }}</div></div>
                    </div>
                    <div class="text-[13px] font-semibold mb-1.5">Contact</div>
                    <div class="text-[13px] text-gray-500 leading-relaxed mb-6">{{ $selected->email }}<br>{{ $selected->phone ?? 'No phone' }}<br>Joined {{ $selected->created_at?->format('M j, Y') }}</div>

                    <div class="text-[13px] font-semibold mb-2.5">Order history</div>
                    @forelse($selected->orders as $o)
                        @php $st = order_status_style($o->status->value); @endphp
                        <a href="{{ route('admin.orders.show', $o) }}" class="flex items-center justify-between border border-[#F0ECE9] rounded-[9px] px-3.5 py-3 mb-2.5 hover:border-wine-700">
                            <div><div class="font-mono text-[13px]">{{ $o->order_number }}</div><div class="text-xs text-gray-400">{{ $o->created_at?->format('M j, Y') }}</div></div>
                            <div class="flex items-center gap-2.5"><span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:{{ $st['bg'] }};color:{{ $st['color'] }};">{{ $o->status->label() }}</span><span class="font-semibold text-sm">{{ $symbol }}{{ number_format($o->total_amount, 0) }}</span></div>
                        </a>
                    @empty
                        <div class="text-[13px] text-gray-400">No orders yet.</div>
                    @endforelse
                </div>

                <div class="px-6 py-4.5 border-t border-[#E9E4E0] flex gap-2.5">
                    <a href="mailto:{{ $selected->email }}" class="inline-flex items-center justify-center gap-1.5 flex-1 h-[42px] border border-[#E9E4E0] rounded-lg bg-white text-gray-700 text-sm font-semibold hover:border-wine-700"><x-ui.icon name="mail" :size="15" />Email customer</a>
                    <form method="POST" action="{{ route('admin.customers.update', $selected) }}" class="flex-1">
                        @csrf @method('PUT')
                        <input type="hidden" name="is_active" value="{{ $selected->is_active ? 0 : 1 }}">
                        @if($selected->is_active)
                            <button class="inline-flex items-center justify-center gap-1.5 w-full h-[42px] border border-red-300 rounded-lg bg-white text-red-600 text-sm font-semibold hover:bg-red-50"><x-ui.icon name="ban" :size="15" />Suspend</button>
                        @else
                            <button class="inline-flex items-center justify-center gap-1.5 w-full h-[42px] rounded-lg bg-wine-700 hover:bg-[#2A2220] text-white text-sm font-semibold"><x-ui.icon name="refresh" :size="15" />Reactivate</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Slide-over drawer: guest --}}
@if($selectedGuest)
    <div x-data="{ open: true }" x-init="$nextTick(() => open = true)">
        <div x-show="open" x-cloak class="fixed inset-0 z-[75]">
            <a href="{{ route('admin.customers.index', ['tab' => 'guests'] + request()->only('search')) }}" class="absolute inset-0 bg-[#1A1413]/40 block"></a>
            <div class="absolute top-0 right-0 h-full w-[440px] max-w-[92vw] bg-white shadow-2xl flex flex-col"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
                <div class="flex items-center justify-between px-6 py-5 border-b border-[#E9E4E0]">
                    <div class="flex items-center gap-3">
                        <span class="w-11 h-11 rounded-full bg-[#F1E4D0] text-gray-500 grid place-items-center font-semibold text-[17px]">{{ strtoupper(substr($selectedGuest['name'] ?? '?', 0, 1)) }}</span>
                        <div>
                            <div class="text-base font-semibold">{{ $selectedGuest['name'] ?? '—' }}</div>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-[#F3F4F6] text-gray-600">Guest</span>
                        </div>
                    </div>
                    <a href="{{ route('admin.customers.index', ['tab' => 'guests'] + request()->only('search')) }}" class="text-gray-500">✕</a>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-5.5">
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <div class="bg-[#FBEFDD] rounded-[10px] p-3.5"><div class="text-xs text-gray-400 mb-1">Total spent</div><div class="text-xl font-semibold">{{ $symbol }}{{ number_format($selectedGuest['orders_total'] ?? 0, 0) }}</div></div>
                        <div class="bg-[#FBEFDD] rounded-[10px] p-3.5"><div class="text-xs text-gray-400 mb-1">Orders</div><div class="text-xl font-semibold">{{ $selectedGuest['orders_count'] }}</div></div>
                    </div>
                    <div class="text-[13px] font-semibold mb-1.5">Contact</div>
                    <div class="text-[13px] text-gray-500 leading-relaxed mb-6">{{ $selectedGuest['email'] }}<br>{{ $selectedGuest['phone'] ?? 'No phone' }}<br>First order {{ $selectedGuest['first_order_at']?->format('M j, Y') }}</div>

                    <div class="text-[13px] font-semibold mb-2.5">Order history</div>
                    @foreach($selectedGuest['orders'] as $o)
                        @php $st = order_status_style($o->status->value); @endphp
                        <a href="{{ route('admin.orders.show', $o) }}" class="flex items-center justify-between border border-[#F0ECE9] rounded-[9px] px-3.5 py-3 mb-2.5 hover:border-wine-700">
                            <div><div class="font-mono text-[13px]">{{ $o->order_number }}</div><div class="text-xs text-gray-400">{{ $o->created_at?->format('M j, Y') }}</div></div>
                            <div class="flex items-center gap-2.5"><span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:{{ $st['bg'] }};color:{{ $st['color'] }};">{{ $o->status->label() }}</span><span class="font-semibold text-sm">{{ $symbol }}{{ number_format($o->total_amount, 0) }}</span></div>
                        </a>
                    @endforeach
                </div>

                <div class="px-6 py-4.5 border-t border-[#E9E4E0]">
                    <a href="mailto:{{ $selectedGuest['email'] }}" class="inline-flex items-center justify-center gap-1.5 w-full h-[42px] border border-[#E9E4E0] rounded-lg bg-white text-gray-700 text-sm font-semibold hover:border-wine-700"><x-ui.icon name="mail" :size="15" />Email guest</a>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
