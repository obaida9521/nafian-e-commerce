@extends('layouts.admin')

@section('title', 'অর্ডার')

@php $symbol = config('shop.currency_symbol'); @endphp

@section('content')
<div class="space-y-5">
    <x-ui.breadcrumb :items="['Dashboard' => route('admin.dashboard'), 'Orders' => null]" />

    <form method="GET" class="flex flex-wrap gap-3">
        <input name="search" value="{{ request('search') }}" placeholder="Order #, name or phone…"
            class="flex-1 min-w-48 rounded-lg border border-gray-300 px-3.5 py-2 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none">
        <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white">
            <option value="">সব অবস্থা</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status')===$status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <button class="inline-flex items-center gap-1.5 rounded-lg bg-wine-700 px-4 py-2 text-sm font-medium text-cream-100 hover:bg-wine-800"><x-ui.icon name="filter" :size="15" />Filter</button>
    </form>

    <div class="rounded-xl bg-white border border-cream-300/60 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-cream-50 text-gray-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3">Order</th>
                    <th class="text-left font-medium px-5 py-3">Customer</th>
                    <th class="text-center font-medium px-5 py-3">Items</th>
                    <th class="text-right font-medium px-5 py-3">Total</th>
                    <th class="text-center font-medium px-5 py-3">Status</th>
                    <th class="text-left font-medium px-5 py-3">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $order)
                    <tr class="hover:bg-cream-50/50 cursor-pointer" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                        <td class="px-5 py-3 font-medium text-wine-700">{{ $order->order_number }}</td>
                        <td class="px-5 py-3">
                            <div class="text-gray-900">{{ $order->shipping_name }}</div>
                            <div class="text-xs text-gray-400">{{ $order->user?->email ?? $order->guest_phone }}</div>
                        </td>
                        <td class="px-5 py-3 text-center text-gray-700">{{ $order->items_count }}</td>
                        <td class="px-5 py-3 text-right font-medium text-gray-900">{{ $symbol }}{{ number_format($order->total_amount, 0) }}</td>
                        <td class="px-5 py-3 text-center">
                            <x-admin.status-badge :color="$order->status->color()" :label="$order->status->label()" />
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $order->created_at->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">কোনো অর্ডার পাওয়া যায়নি।</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $orders->links() }}
</div>
@endsection
