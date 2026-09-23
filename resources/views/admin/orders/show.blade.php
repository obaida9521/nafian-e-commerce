@extends('layouts.admin')
@section('title', 'অর্ডার '.$order->order_number)

@section('content')
<div x-data="{ statusOpen: false, cancelOpen: false }">
    <div class="text-[14px] text-muted">
        <a href="{{ route('admin.orders.index') }}" class="hover:text-accent">অর্ডার</a>
        <span class="mx-1.5">/</span><span class="text-ink">{{ bn_digits($order->order_number) }}</span>
    </div>

    <div class="mt-2.5 flex justify-between items-end gap-3.5 flex-wrap">
        <div>
            <h1 class="font-display text-[28px] sm:text-[32px]">{{ bn_digits($order->order_number) }}</h1>
            <div class="mt-1 text-[14.5px] text-muted">
                {{ bn_date($order->created_at) }}, {{ bn_time($order->created_at) }} · {{ $order->payment_method->labelBn() }}
            </div>
        </div>
        <div class="flex gap-2.5 flex-wrap items-center">
            <x-admin.status-badge :status="$order->status" class="!px-[18px] !py-2.5 !text-[14px]" />
            <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-white rounded-full px-5 py-2.5 text-[14px] font-medium"><x-ui.icon name="printer" :size="16" />ইনভয়েস প্রিন্ট</a>
            @adminCan('orders')
                @if($nextStatuses)
                    <div class="relative" @click.outside="statusOpen = false">
                        <button @click="statusOpen = ! statusOpen" class="inline-flex items-center gap-1.5 bg-mocha text-white rounded-full px-5 py-2.5 text-[14px] font-medium"><x-ui.icon name="refresh" :size="16" />অবস্থা বদলান<x-ui.icon name="chevron-down" :size="15" /></button>
                        <div x-show="statusOpen" x-cloak x-transition.opacity class="absolute right-0 mt-2 w-56 bg-white rounded-2xl p-2 z-20 shadow-[0_18px_40px_-16px_rgba(36,28,26,.45)]">
                            @foreach($nextStatuses as $next)
                                @continue($next === \App\Enums\OrderStatus::Cancelled)
                                <form method="POST" action="{{ route('admin.orders.update-status', $order) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $next->value }}">
                                    <button class="w-full text-left px-3.5 py-2.5 rounded-xl text-[14.5px] hover:bg-canvas flex items-center gap-2"><x-ui.icon name="arrow-right" :size="15" />{{ $next->labelBn() }} হিসেবে চিহ্নিত করুন</button>
                                </form>
                            @endforeach
                            @if($cancellable)
                                <button @click="cancelOpen = true; statusOpen = false" class="w-full text-left px-3.5 py-2.5 rounded-xl text-[14.5px] text-rose hover:bg-rose-soft flex items-center gap-2"><x-ui.icon name="x-circle" :size="15" />অর্ডার বাতিল করুন</button>
                            @endif
                        </div>
                    </div>
                @endif
            @endadminCan
        </div>
    </div>

    <div class="mt-5 grid desk:grid-cols-[1fr_360px] gap-[18px] items-start">
        <div class="flex flex-col gap-[18px]">
            {{-- Items --}}
            <x-admin.card title="পণ্য">
                <div class="mt-4 hidden sm:grid grid-cols-[1fr_110px_90px_110px] gap-3 px-2.5 pb-2.5 text-[13px] font-semibold text-muted">
                    <div>পণ্য</div><div>দাম</div><div>পরিমাণ</div><div>মোট</div>
                </div>
                @foreach($order->items as $item)
                    <div @class([
                        'grid grid-cols-2 sm:grid-cols-[1fr_110px_90px_110px] gap-x-3 gap-y-1.5 items-center px-2.5 py-3.5 rounded-2xl',
                        'bg-canvas' => $loop->even,
                    ])>
                        <div class="flex gap-3 items-center max-sm:col-span-2">
                            <x-ui.product-image :product="$item->variant?->product" :variant="$item->variant" class="w-11 h-11 rounded-[9px] flex-none" />
                            <div class="min-w-0">
                                <div class="text-[15px] font-semibold truncate">{{ $item->product_name }}</div>
                                <div class="text-[13px] text-muted">SKU {{ $item->sku }} · {{ bn_digits($item->variant_name) }}</div>
                            </div>
                        </div>
                        <div class="text-[14.5px]"><span class="sm:hidden text-muted">দাম </span>{{ bn_price($item->unit_price) }}</div>
                        <div class="text-[14.5px]"><span class="sm:hidden text-muted">পরিমাণ </span>{{ bn_digits($item->quantity) }}</div>
                        <div class="text-[14.5px] font-semibold max-sm:col-span-2 max-sm:text-right">{{ bn_price($item->line_total) }}</div>
                    </div>
                @endforeach

                <div class="nf-line my-[18px]"></div>
                <div class="flex flex-col gap-2.5 text-[15px] text-cocoa max-w-[340px] ml-auto">
                    <div class="flex justify-between"><span>সাবটোটাল</span><span class="text-ink">{{ bn_price($order->subtotal) }}</span></div>
                    @if($order->discount_amount > 0)
                        <div class="flex justify-between"><span>ছাড়@if($order->coupon_code) ({{ $order->coupon_code }})@endif</span><span class="text-accent">−{{ bn_price($order->discount_amount) }}</span></div>
                    @endif
                    <div class="flex justify-between"><span>ডেলিভারি</span><span class="text-ink">{{ bn_price($order->delivery_charge) }}</span></div>
                    <div class="flex justify-between text-[17px] font-semibold text-ink"><span>সর্বমোট</span><span>{{ bn_price($order->total_amount) }}</span></div>
                </div>
            </x-admin.card>

            {{-- History + internal note --}}
            <x-admin.card title="অর্ডারের ইতিহাস">
                <div class="mt-4 flex flex-col gap-3.5">
                    @foreach($timeline as $entry)
                        <div class="flex gap-3.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-mocha mt-1.5 flex-none"></span>
                            <div>
                                <div class="text-[15px] font-semibold">{{ $entry['label'] }}</div>
                                <div class="text-[13.5px] text-muted">
                                    {{ bn_date($entry['at'], 'j M') }}, {{ bn_time($entry['at']) }}@if($entry['note']) · {{ $entry['note'] }}@endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="nf-line my-[18px]"></div>
                <form method="POST" action="{{ route('admin.orders.details', $order) }}">
                    @csrf @method('PATCH')
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="nf-label" for="rider-name">রাইডারের নাম</label>
                            <input id="rider-name" name="rider_name" value="{{ old('rider_name', $order->rider_name) }}" class="nf-input nf-input-soft" placeholder="শাকিল আহমেদ">
                        </div>
                        <div>
                            <label class="nf-label" for="rider-phone">রাইডারের নম্বর</label>
                            <input id="rider-phone" name="rider_phone" value="{{ old('rider_phone', $order->rider_phone) }}" class="nf-input nf-input-soft" placeholder="০১৯০০ ১১২ ২৩৩">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="nf-label" for="admin-notes">অভ্যন্তরীণ নোট</label>
                        <textarea id="admin-notes" name="admin_notes" rows="2" class="nf-input nf-input-soft" placeholder="যেমন: গ্রাহক বিকেল ৫টার পরে ডেলিভারি চেয়েছেন।">{{ old('admin_notes', $order->admin_notes) }}</textarea>
                    </div>
                    @adminCan('orders')
                        <button class="mt-4 inline-flex items-center gap-1.5 bg-mocha text-white rounded-full px-6 py-3 text-[14px] font-semibold"><x-ui.icon name="check" :size="15" />সংরক্ষণ করুন</button>
                    @endadminCan
                </form>
            </x-admin.card>
        </div>

        <div class="flex flex-col gap-[18px]">
            <x-admin.card title="গ্রাহক">
                <div class="mt-3 text-[15px] leading-[1.9] text-bark">
                    {{ $order->shipping_name }}<br>
                    <a href="tel:{{ $order->shipping_phone }}" class="hover:text-accent">{{ bn_phone($order->shipping_phone) }}</a><br>
                    @if($order->guest_email || $order->user?->email)
                        <span class="text-muted">{{ $order->guest_email ?: $order->user?->email }}</span><br>
                    @endif
                    <span class="text-muted">আগে {{ bn_digits($previousOrders) }}টি অর্ডার</span>
                </div>

                <div class="nf-line my-[18px]"></div>
                <div class="text-[15.5px] font-semibold">ডেলিভারি ঠিকানা</div>
                <div class="mt-2.5 text-[14.5px] leading-[1.9] text-cocoa">
                    {{ $order->shipping_address }}<br>
                    {{ collect([$order->shipping_area, $order->shipping_city, bn_digits($order->shipping_postcode)])->filter()->implode(', ') }}<br>
                    <span class="text-muted">{{ $order->delivery_zone === 'outside' ? 'ঢাকার বাইরে' : 'ঢাকার ভেতরে' }}</span>
                </div>

                <div class="nf-line my-[18px]"></div>
                <div class="text-[15.5px] font-semibold">পেমেন্ট</div>
                <div class="mt-2.5 flex justify-between items-center text-[14.5px] text-cocoa">
                    <span>{{ $order->payment_method->labelBn() }}</span>
                    @php $payment = $order->payments->first(); @endphp
                    <span class="bg-clay-soft rounded-full px-3 py-1.5 text-[13px] font-semibold text-bark">
                        {{ $payment && $payment->status === \App\Enums\PaymentStatus::Paid ? 'পরিশোধিত' : 'বকেয়া' }}
                    </span>
                </div>

                @if($order->notes)
                    <div class="nf-line my-[18px]"></div>
                    <div class="text-[15.5px] font-semibold">গ্রাহকের নোট</div>
                    <p class="mt-2 text-[14.5px] leading-[1.8] text-cocoa">{{ $order->notes }}</p>
                @endif
            </x-admin.card>

            @if($order->rider_name || $order->rider_phone)
                <x-admin.card>
                    <div class="text-[15.5px] font-semibold">রাইডার</div>
                    <div class="mt-3 text-[14.5px] leading-[1.9] text-bark">
                        {{ $order->rider_name }}<br>{{ bn_phone($order->rider_phone) }}
                    </div>
                    @if($order->rider_phone)
                        <a href="sms:{{ $order->rider_phone }}?body={{ rawurlencode('অর্ডার '.$order->order_number.' · '.$order->shipping_address) }}"
                           class="mt-3.5 flex items-center justify-center gap-1.5 bg-accent-soft text-accent rounded-full py-3.5 text-[14.5px] font-semibold"><x-ui.icon name="message" :size="16" />এসএমএস পাঠান</a>
                    @endif
                </x-admin.card>
            @endif
        </div>
    </div>

    {{-- Cancel dialog --}}
    @adminCan('orders')
        <div x-show="cancelOpen" x-cloak class="fixed inset-0 z-[80] grid place-items-center bg-[#1A1413]/50 px-5" @click.self="cancelOpen = false">
            <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="bg-white rounded-[22px] p-6 w-full max-w-[420px]">
                @csrf
                <div class="text-[18px] font-semibold">অর্ডার বাতিল করুন</div>
                <p class="mt-1.5 text-[14.5px] text-muted">বাতিল করলে রিজার্ভ করা স্টক ছেড়ে দেওয়া হবে।</p>
                <label class="nf-label mt-4" for="cancel-reason">কারণ</label>
                <textarea id="cancel-reason" name="cancelled_reason" rows="3" required class="nf-input nf-input-soft" placeholder="যেমন: গ্রাহক ফোনে বাতিল করেছেন"></textarea>
                <div class="mt-4 flex gap-2.5">
                    <button type="button" @click="cancelOpen = false" class="inline-flex items-center justify-center gap-1.5 flex-1 bg-hair rounded-full py-3 text-[14.5px] font-semibold"><x-ui.icon name="arrow-left" :size="15" />ফিরে যান</button>
                    <button class="inline-flex items-center justify-center gap-1.5 flex-1 bg-rose text-white rounded-full py-3 text-[14.5px] font-semibold"><x-ui.icon name="x-circle" :size="15" />বাতিল করুন</button>
                </div>
            </form>
        </div>
    @endadminCan
</div>
@endsection
