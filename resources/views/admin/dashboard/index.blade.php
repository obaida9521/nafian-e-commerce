@extends('layouts.admin')

@section('title', 'Dashboard')

@php
    $symbol = config('shop.currency_symbol');
    $statusColors = [
        'pending' => '#f59e0b', 'confirmed' => '#3b82f6', 'processing' => '#6366f1',
        'shipped' => '#a855f7', 'delivered' => '#22c55e', 'cancelled' => '#ef4444', 'refunded' => '#6b7280',
    ];
@endphp

@section('content')
<div class="space-y-6">

    {{-- Stat cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat-card title="Today's Orders" :value="number_format($stats['today_orders'])" color="wine"
            icon="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
        <x-admin.stat-card title="Today's Revenue" :value="$symbol . number_format($stats['today_revenue'], 0)" color="green"
            icon="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 8v2m0-10c1.11 0 2.08.402 2.599 1M12 16c-1.11 0-2.08-.402-2.599-1" />
        <x-admin.stat-card title="Total Products" :value="number_format($stats['total_products'])" color="blue"
            icon="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
        <x-admin.stat-card title="Low Stock" :value="number_format($stats['low_stock_count'])" color="amber"
            icon="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
    </div>

    {{-- Profit & loss --}}
    @php
        $netPositive = $monthPnl['net_profit'] >= 0;
        $margin = $monthPnl['total_revenue'] > 0 ? round($monthPnl['net_profit'] / $monthPnl['total_revenue'] * 100, 1) : 0;
    @endphp
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl bg-white border border-cream-300/60 p-5 shadow-sm">
            <div class="text-[13px] text-gray-500 mb-1.5">Revenue · {{ $monthLabel }}</div>
            <div class="text-[26px] font-semibold tracking-tight">{{ $symbol }}{{ number_format($monthPnl['total_revenue'], 0) }}</div>
        </div>
        <div class="rounded-xl bg-white border border-cream-300/60 p-5 shadow-sm">
            <div class="text-[13px] text-gray-500 mb-1.5">Cost of goods</div>
            <div class="text-[26px] font-semibold tracking-tight text-red-700">−{{ $symbol }}{{ number_format($monthPnl['total_cogs'], 0) }}</div>
        </div>
        <div class="rounded-xl bg-white border border-cream-300/60 p-5 shadow-sm">
            <div class="text-[13px] text-gray-500 mb-1.5">Expenses</div>
            <div class="text-[26px] font-semibold tracking-tight text-red-700">−{{ $symbol }}{{ number_format($monthPnl['expenses_total'], 0) }}</div>
        </div>
        <div class="rounded-xl p-5 shadow-sm border {{ $netPositive ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
            <div class="text-[13px] {{ $netPositive ? 'text-green-700' : 'text-red-700' }} mb-1.5">Net profit · margin {{ $margin }}%</div>
            <div class="text-[26px] font-semibold tracking-tight {{ $netPositive ? 'text-green-700' : 'text-red-700' }}">
                {{ $netPositive ? '' : '−' }}{{ $symbol }}{{ number_format(abs($monthPnl['net_profit']), 0) }}
            </div>
        </div>
    </div>

    {{-- Monthly profit/loss chart --}}
    <div class="rounded-xl bg-white border border-cream-300/60 p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-900">Profit &amp; Loss — Last 12 Months</h2>
            <a href="{{ route('admin.reports.index') }}" class="text-[13px] text-wine-700 font-semibold hover:underline">Full report →</a>
        </div>
        <div class="relative h-[300px]"><canvas id="pnlChart"></canvas></div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Sales chart --}}
        <div class="lg:col-span-2 rounded-xl bg-white border border-cream-300/60 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-900">Sales — Last 7 Days</h2>
            </div>
            <div class="relative h-[260px]"><canvas id="salesChart"></canvas></div>
        </div>

        {{-- Status breakdown --}}
        <div class="rounded-xl bg-white border border-cream-300/60 p-5 shadow-sm">
            <h2 class="font-semibold text-gray-900 mb-4">Order Status</h2>
            <div class="relative h-[260px]"><canvas id="statusChart"></canvas></div>
        </div>
    </div>

    {{-- Top products --}}
    <div class="rounded-xl bg-white border border-cream-300/60 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-cream-300/60">
            <h2 class="font-semibold text-gray-900">Top Products</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-cream-50 text-gray-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3">Product</th>
                    <th class="text-right font-medium px-5 py-3">Units Sold</th>
                    <th class="text-right font-medium px-5 py-3">Revenue</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($topProducts as $p)
                    <tr class="hover:bg-cream-50/50">
                        <td class="px-5 py-3 text-gray-900">{{ $p->product_name }}</td>
                        <td class="px-5 py-3 text-right text-gray-700">{{ number_format($p->units_sold) }}</td>
                        <td class="px-5 py-3 text-right font-medium text-gray-900">{{ $symbol }}{{ number_format($p->revenue, 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-8 text-center text-gray-400">No sales yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('head')
<script>
    window.__dashboard = {
        sales: @json($salesChart),
        status: @json($statusBreakdown),
        statusColors: @json($statusColors),
        pnl: @json($monthlyPnl),
        symbol: @json($symbol),
    };
</script>
@endpush
