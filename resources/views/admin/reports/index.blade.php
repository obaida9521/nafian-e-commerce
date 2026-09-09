@extends('layouts.admin')
@section('title', 'Reports')

@php $symbol = config('shop.currency_symbol'); @endphp

@section('content')
<div class="max-w-[1280px] mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4.5">
        <div class="flex gap-1.5 bg-[#F6EAD8] p-[3px] rounded-lg">
            @foreach(['today'=>'Today','7d'=>'7 days','30d'=>'30 days'] as $key=>$label)
                <a href="{{ route('admin.reports.index', ['range' => $key]) }}"
                   class="h-8 px-3.5 grid place-items-center rounded-md text-[12.5px] font-semibold {{ $activeRange === $key ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500' }}">{{ $label }}</a>
            @endforeach
            <span class="h-8 px-3.5 grid place-items-center rounded-md text-[12.5px] font-semibold {{ $activeRange === 'custom' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500' }}">Custom</span>
        </div>
        <div class="flex items-center gap-2.5">
            <form method="GET" class="flex items-center gap-2">
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="h-10 border border-[#EADBC4] rounded-lg px-3 text-sm bg-white outline-none focus:border-wine-700">
                <span class="text-gray-400 text-sm">→</span>
                <input type="date" name="to" value="{{ $to->toDateString() }}" class="h-10 border border-[#EADBC4] rounded-lg px-3 text-sm bg-white outline-none focus:border-wine-700">
                <button class="h-10 px-4 rounded-lg bg-wine-700 hover:bg-[#4d141e] text-white text-sm font-semibold">Apply</button>
            </form>
            <a href="{{ route('admin.reports.export', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
               class="h-10 px-4 border border-[#EADBC4] rounded-lg bg-white text-gray-700 text-sm font-semibold flex items-center gap-2 hover:border-wine-700">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12M7 11l5 4 5-4M5 21h14"/></svg>Export CSV
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4.5 mb-4.5">
        @foreach([
            ['Net revenue', $symbol.number_format($report['net_revenue'], 0)],
            ['Gross revenue', $symbol.number_format($report['gross_revenue'], 0)],
            ['Discounts', $symbol.number_format($report['discount_total'], 0)],
            ['Orders', number_format($report['order_count'])],
        ] as [$label, $value])
            <div class="bg-white border border-[#EADBC4] rounded-xl p-5">
                <div class="text-[13px] text-gray-500 mb-2.5">{{ $label }}</div>
                <div class="text-[28px] font-semibold tracking-tight">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    {{-- Profit & loss --}}
    @php
        $pl = $profitLoss;
        $catLabels = collect(\App\Enums\ExpenseCategory::cases())->keyBy(fn($c) => $c->value);
    @endphp
    <div class="bg-white border border-[#EADBC4] rounded-xl p-5.5 mb-4.5">
        <div class="text-[15px] font-semibold mb-4.5">Profit &amp; loss ({{ $from->format('M j') }} – {{ $to->format('M j, Y') }})</div>
        <div class="grid md:grid-cols-2 gap-x-8 gap-y-2 text-[13.5px]">
            <div class="flex justify-between py-2 border-b border-[#F6EAD8]">
                <span class="text-gray-600">Online order revenue</span>
                <span class="font-semibold">{{ $symbol }}{{ number_format($pl['order_revenue'], 0) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-[#F6EAD8]">
                <span class="text-gray-600">POS sale revenue</span>
                <span class="font-semibold">{{ $symbol }}{{ number_format($pl['pos_revenue'], 0) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-[#F6EAD8]">
                <span class="text-gray-600">Total revenue</span>
                <span class="font-semibold">{{ $symbol }}{{ number_format($pl['total_revenue'], 0) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-[#F6EAD8]">
                <span class="text-gray-600">Cost of goods sold</span>
                <span class="font-semibold text-red-700">−{{ $symbol }}{{ number_format($pl['total_cogs'], 0) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-[#F6EAD8]">
                <span class="text-gray-600">Gross profit</span>
                <span class="font-semibold">{{ $symbol }}{{ number_format($pl['gross_profit'], 0) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-[#F6EAD8]">
                <span class="text-gray-600">Total expenses</span>
                <span class="font-semibold text-red-700">−{{ $symbol }}{{ number_format($pl['expenses_total'], 0) }}</span>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-[#EADBC4] flex items-center justify-between">
            <span class="text-[15px] font-semibold">Net profit</span>
            <span class="text-[24px] font-semibold tracking-tight {{ $pl['net_profit'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                {{ $pl['net_profit'] < 0 ? '−' : '' }}{{ $symbol }}{{ number_format(abs($pl['net_profit']), 0) }}
            </span>
        </div>
        @if($pl['expenses_total'] > 0)
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($pl['expenses_by_category'] as $cat => $amount)
                    @if($amount > 0)
                        <span class="text-[12px] px-2.5 py-1 rounded-full bg-[#F6EAD8] text-gray-700">
                            {{ $catLabels[$cat]?->label() ?? $cat }}: {{ $symbol }}{{ number_format($amount, 0) }}
                        </span>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Revenue chart --}}
    <div class="bg-white border border-[#EADBC4] rounded-xl p-5.5 mb-4.5">
        <div class="text-[15px] font-semibold mb-4.5">Revenue (last 30 days)</div>
        <div class="h-[280px] relative"><canvas id="repRevenue"></canvas></div>
    </div>

    {{-- Orders + payment --}}
    <div class="grid lg:grid-cols-[1.5fr_1fr] gap-4.5 mb-4.5">
        <div class="bg-white border border-[#EADBC4] rounded-xl p-5.5">
            <div class="text-[15px] font-semibold mb-4.5">Orders by day</div>
            <div class="h-[240px] relative"><canvas id="repOrders"></canvas></div>
        </div>
        <div class="bg-white border border-[#EADBC4] rounded-xl p-5.5">
            <div class="text-[15px] font-semibold mb-4.5">Payment method</div>
            <div class="h-[240px] relative"><canvas id="repPayment"></canvas></div>
        </div>
    </div>

    {{-- Top products --}}
    <div class="bg-white border border-[#EADBC4] rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-[#EFE2CE] text-[15px] font-semibold">Top products</div>
        <div class="grid grid-cols-[1fr_100px_120px] px-5 py-3 text-[11px] tracking-wide uppercase text-gray-400 font-semibold border-b border-[#EFE2CE]">
            <span>Product</span><span class="text-right">Units</span><span class="text-right">Revenue</span>
        </div>
        @forelse($topProducts as $p)
            <div class="grid grid-cols-[1fr_100px_120px] px-5 py-3 items-center border-b border-[#F6EAD8] text-[13.5px]">
                <span class="font-semibold">{{ $p->product_name }}</span>
                <span class="text-right text-gray-500">{{ number_format($p->units_sold) }}</span>
                <span class="text-right font-semibold">{{ $symbol }}{{ number_format($p->revenue, 0) }}</span>
            </div>
        @empty
            <div class="px-5 py-8 text-center text-gray-400">No data for this period.</div>
        @endforelse
    </div>
</div>
@endsection

@push('head')
<script>
    window.__report = {
        sales: @json($salesChart),
        payment: @json($paymentSplit),
        symbol: @json($symbol),
    };
</script>
@endpush
