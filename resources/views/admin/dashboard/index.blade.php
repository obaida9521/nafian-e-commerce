@extends('layouts.admin')
@section('title', 'ড্যাশবোর্ড')

@php
    $maxDaily = max(1, collect($overview['daily'])->max('total'));
    $peak = collect($overview['daily'])->sortByDesc('total')->first();
    $statusLabels = [
        'new' => ['নতুন অর্ডার', '#3C5A78'],
        'processing' => ['প্রসেসিং', '#2A2220'],
        'shipped' => ['শিপড', '#2A2220'],
        'delivered' => ['ডেলিভারড', '#2A2220'],
        'cancelled' => ['বাতিল / রিটার্ন', '#8A5A52'],
    ];
    $statusMax = max(1, max($overview['statuses']));
    $tabs = ['all' => 'সব', 'new' => 'নতুন', 'processing' => 'প্রসেসিং', 'shipped' => 'শিপড'];
@endphp

@section('content')
<div class="flex justify-between items-end gap-4 flex-wrap">
    <div>
        <h1 class="font-display text-[28px] sm:text-[32px]">ড্যাশবোর্ড</h1>
        <div class="mt-1 text-[14.5px] text-muted">{{ bn_date(now()) }} · গত {{ bn_digits($days) }} দিন</div>
    </div>
    <div class="flex gap-2.5 flex-wrap items-center">
        <form method="GET" class="flex">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <select name="days" onchange="this.form.submit()" class="bg-white rounded-full px-5 py-2.5 text-[14px] font-medium outline-none cursor-pointer">
                @foreach([7 => 'গত ৭ দিন', 30 => 'গত ৩০ দিন', 90 => 'গত ৯০ দিন'] as $value => $label)
                    <option value="{{ $value }}" @selected($days === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        @adminCan('products')
            <a href="{{ route('admin.products.create') }}" class="inline-flex items-center gap-1.5 bg-mocha text-white rounded-full px-5 py-2.5 text-[14px] font-medium"><x-ui.icon name="plus" :size="16" />নতুন পণ্য</a>
        @endadminCan
    </div>
</div>

{{-- KPIs --}}
<div class="mt-[22px] grid grid-cols-2 desk:grid-cols-4 gap-3.5 sm:gap-[18px]">
    <x-admin.stat-card title="মোট বিক্রি" :value="bn_price($overview['revenue'])" :change="$overview['revenue_change']" hint="আগের সময়ের চেয়ে" />
    <x-admin.stat-card title="অর্ডার" :value="bn_digits($overview['orders'])" :change="$overview['orders_change']" />
    <x-admin.stat-card title="গড় অর্ডার মূল্য" :value="bn_price($overview['average_order'])" :change="$overview['average_order_change']" />
    <x-admin.stat-card
        title="COD ডেলিভারি সফল"
        :value="$overview['cod_success_rate'] !== null ? bn_digits($overview['cod_success_rate']).'%' : '—'"
        :hint="bn_digits($overview['returned']).'টি বাতিল / রিটার্ন'" />
</div>

{{-- Chart + status --}}
<div class="mt-[18px] grid desk:grid-cols-[1.6fr_1fr] gap-[18px] items-start">
    <x-admin.card>
        <div class="flex justify-between items-baseline flex-wrap gap-2.5">
            <div class="text-[17px] font-semibold">দৈনিক বিক্রি</div>
            @if($peak && $peak['total'] > 0)
                <div class="text-[13.5px] text-muted">সর্বোচ্চ {{ bn_price($peak['total']) }} · {{ bn_date($peak['date'], 'j M') }}</div>
            @endif
        </div>
        <div class="mt-[22px] flex items-end gap-1.5 h-[220px]">
            @foreach($overview['daily'] as $day)
                @php $isPeak = $peak && $day['date']->isSameDay($peak['date']) && $day['total'] > 0; @endphp
                <div class="flex-1 rounded-t-lg min-h-[4px] transition-[height]"
                     style="height:{{ max(4, round($day['total'] / $maxDaily * 100)) }}%;background:{{ $isPeak ? '#2A2220' : '#EDE8E5' }};"
                     title="{{ bn_date($day['date'], 'j M') }} · {{ bn_price($day['total']) }}"></div>
            @endforeach
        </div>
        <div class="mt-2.5 flex justify-between text-[12.5px] text-muted">
            <span>{{ bn_date($overview['daily'][0]['date'], 'j M') }}</span>
            <span>{{ bn_date($overview['daily'][intdiv(count($overview['daily']), 2)]['date'], 'j M') }}</span>
            <span>{{ bn_date(end($overview['daily'])['date'], 'j M') }}</span>
        </div>
    </x-admin.card>

    <x-admin.card title="অর্ডারের অবস্থা">
        <div class="mt-[18px] flex flex-col gap-3.5">
            @foreach($statusLabels as $key => [$label, $color])
                <div>
                    <div class="flex justify-between text-[14.5px] text-bark"><span>{{ $label }}</span><span class="font-semibold">{{ bn_digits($overview['statuses'][$key]) }}</span></div>
                    <div class="mt-[7px] h-2 rounded-full bg-hair">
                        <div class="h-2 rounded-full" style="width:{{ round($overview['statuses'][$key] / $statusMax * 100) }}%;background:{{ $color }};"></div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($overview['low_stock']->isNotEmpty())
            <div class="mt-[22px] bg-canvas rounded-2xl p-4 text-[14px] leading-[1.7] text-bark">
                <span class="font-semibold">{{ bn_digits($overview['low_stock']->count()) }}টি ভ্যারিয়েন্টের স্টক কম।</span><br>
                @foreach($overview['low_stock']->take(2) as $variant)
                    {{ $variant->product?->name }} {{ bn_digits($variant->shortLabel()) }} — {{ bn_digits($variant->available_quantity) }}টি বাকি।<br>
                @endforeach
                <a href="{{ route('admin.inventory.index') }}" class="text-accent">ইনভেন্টরি দেখুন →</a>
            </div>
        @endif
    </x-admin.card>
</div>

{{-- Recent orders --}}
<x-admin.card class="mt-[18px]">
    <div class="flex justify-between items-center gap-3 flex-wrap">
        <div class="text-[17px] font-semibold">সাম্প্রতিক অর্ডার</div>
        <div class="flex gap-2 flex-wrap">
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.dashboard', ['tab' => $key, 'days' => $days]) }}" @class([
                    'rounded-full px-4 py-2 text-[13.5px] font-medium',
                    'bg-mocha text-white' => $tab === $key,
                    'bg-[#F5F2F0] text-ink' => $tab !== $key,
                ])>{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="mt-[18px] hidden sm:grid grid-cols-[120px_1fr_130px_120px_130px_100px] gap-3 px-1.5 pb-3 text-[13px] font-semibold text-muted">
        <div>অর্ডার</div><div>গ্রাহক</div><div>পেমেন্ট</div><div>মোট</div><div>অবস্থা</div><div>তারিখ</div>
    </div>

    @forelse($recentOrders as $order)
        <a href="{{ route('admin.orders.show', $order) }}" @class([
            'grid grid-cols-2 sm:grid-cols-[120px_1fr_130px_120px_130px_100px] gap-x-3 gap-y-1.5 items-center px-1.5 py-3.5 rounded-2xl',
            'bg-canvas' => $loop->even,
        ])>
            <div class="text-[14.5px] font-semibold">{{ bn_digits($order->order_number) }}</div>
            <div class="text-[14.5px] text-bark max-sm:order-3 max-sm:col-span-2">
                {{ $order->shipping_name }}
                <div class="text-[13px] text-muted">{{ collect([$order->shipping_area, $order->shipping_city])->filter()->implode(', ') }}</div>
            </div>
            <div class="hidden sm:block text-[14.5px] text-bark">{{ $order->payment_method->shortLabelBn() }}</div>
            <div class="text-[14.5px] font-semibold max-sm:text-right">{{ bn_price($order->total_amount) }}</div>
            <div class="max-sm:order-4"><x-admin.status-badge :status="$order->status" /></div>
            <div class="hidden sm:block text-[14.5px] text-muted">{{ bn_date($order->created_at, 'j M') }}</div>
        </a>
    @empty
        <div class="py-10 text-center text-[14.5px] text-muted">এই ফিল্টারে কোনো অর্ডার নেই।</div>
    @endforelse
</x-admin.card>

{{-- Products --}}
<div class="mt-7 flex justify-between items-end gap-3 flex-wrap">
    <div>
        <h2 class="font-display text-[26px]">পণ্য</h2>
        <div class="mt-1 text-[14px] text-muted">{{ bn_digits($productCount) }}টি পণ্য · {{ bn_digits($variantCount) }}টি ভ্যারিয়েন্ট</div>
    </div>
    <div class="flex gap-2.5 flex-wrap">
        <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center gap-1.5 bg-white rounded-full px-5 py-2.5 text-[14px] font-medium"><x-ui.icon name="box" :size="16" />ইনভেন্টরি দেখুন</a>
        @adminCan('products')
            <a href="{{ route('admin.products.create') }}" class="inline-flex items-center gap-1.5 bg-mocha text-white rounded-full px-5 py-2.5 text-[14px] font-medium"><x-ui.icon name="plus" :size="16" />পণ্য যোগ করুন</a>
        @endadminCan
    </div>
</div>

<div class="mt-4 grid gap-[18px] desk:grid-cols-3">
    @foreach($products as $product)
        @php
            $productVariants = $product->variants->where('is_active', true);
            $stock = (int) $productVariants->sum(fn ($v) => $v->available_quantity);
            $prices = $productVariants->pluck('price')->map(fn ($p) => (float) $p);
            $sizes = $product->sizeOptions();
        @endphp
        <a href="{{ route('admin.products.edit', $product) }}" class="bg-white rounded-[20px] p-[18px] flex gap-4 nf-shadow-soft">
            <x-ui.product-image :product="$product" class="w-[78px] h-[78px] rounded-xl flex-none" />
            <div class="flex-1 min-w-0">
                <div class="text-[16.5px] font-semibold truncate">{{ $product->name }}</div>
                <div class="mt-0.5 text-[13.5px] text-muted truncate">
                    {{ $product->categories->first()?->name }}@if(count($sizes)) · {{ bn_digits(count($sizes)) }}টি সাইজ @endif
                </div>
                <div class="mt-2 text-[15.5px] font-semibold text-espresso">
                    {{ bn_price($prices->min()) }}@if($prices->max() > $prices->min()) – {{ bn_price($prices->max()) }}@endif
                </div>
                <div class="mt-2 flex gap-1.5 flex-wrap">
                    <span @class([
                        'rounded-full px-[11px] py-[5px] text-[12.5px] font-semibold',
                        'bg-rose-soft text-rose' => $stock <= (int) config('shop.low_stock_threshold'),
                        'bg-moss-soft text-moss' => $stock > (int) config('shop.low_stock_threshold'),
                    ])>স্টক {{ bn_digits($stock) }}</span>
                    <span class="bg-[#F5F2F0] rounded-full px-[11px] py-[5px] text-[12.5px] font-medium">{{ $product->is_active ? 'প্রকাশিত' : 'খসড়া' }}</span>
                </div>
            </div>
        </a>
    @endforeach
</div>
@endsection
