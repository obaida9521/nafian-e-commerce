@extends('layouts.app')
@section('title', $title)

@php
    $sortOptions = [
        'best_selling' => 'বেস্ট সেলিং',
        'newest' => 'নতুন',
        'price_asc' => 'দাম: কম থেকে বেশি',
        'price_desc' => 'দাম: বেশি থেকে কম',
        'top_rated' => 'সেরা রেটিং',
    ];
    $defaultSort = \App\Services\CatalogService::SORTS[0];
    $baseUrl = $activeCategory ? route('store.shop.category', $activeCategory->slug) : route('store.shop');
    $priceMin = $facets['price_min'];
    $priceMax = max($facets['price_max'], $priceMin);
    $hasPriceRange = $priceMax > $priceMin;
    $activeFilterCount = count($filters['types']) + count($filters['colors']) + count($filters['sizes'])
        + (int) $filters['in_stock'] + (int) $filters['top_rated'] + (int) $filters['on_sale']
        + (int) ($filters['min'] !== null || $filters['max'] !== null)
        + (int) ($activeCategory !== null);
    $chipBase = 'rounded-full px-4 py-[9px] text-[13.5px] cursor-pointer select-none transition-colors';
    $boxBase = 'inline-block w-4 h-4 rounded-[5px] mr-2.5 align-[-3px] transition-colors';
    $queryWithout = fn (array $except = []) => \Illuminate\Support\Arr::except(request()->except('page'), $except);
@endphp

@section('mobile_header')
    <div class="sm:hidden sticky top-0 z-40 bg-white px-5 pt-3">
        <div class="flex items-center justify-between gap-3">
            <div class="text-[20px] font-semibold truncate">{{ $title }}</div>
            <div class="flex items-center gap-2 flex-none">
                <span class="text-[13.5px] text-muted">{{ bn_digits($products->total()) }}টি পণ্য</span>
                <button type="button" @click="searchOpen = true" class="bg-sand rounded-full w-9 h-9 grid place-items-center text-espresso" aria-label="খুঁজুন"><x-ui.icon name="search" :size="18" :stroke="2" /></button>
            </div>
        </div>
        <div class="mt-3 pb-3 nf-rail gap-[9px]">
            <button type="button" @click="$store.shop.filtersOpen = true" class="flex-none bg-espresso text-white rounded-full px-[18px] py-2.5 text-[13.5px] font-medium">
                ফিল্টার@if($activeFilterCount) · {{ bn_digits($activeFilterCount) }}@endif
            </button>
            <button type="button" @click="$store.shop.sortOpen = true" class="flex-none bg-sand-3 rounded-full px-[18px] py-2.5 text-[13.5px] font-medium">সাজান</button>
            @if($activeCategory)
                <a href="{{ route('store.shop', $queryWithout()) }}" class="flex-none bg-sand-3 rounded-full px-[18px] py-2.5 text-[13.5px] font-medium whitespace-nowrap">{{ $activeCategory->name }} ×</a>
            @endif
            @foreach($filters['sizes'] as $size)
                <a href="{{ $baseUrl }}?{{ http_build_query(array_replace($queryWithout(), ['sizes' => array_values(array_diff($filters['sizes'], [$size]))])) }}"
                   class="flex-none bg-sand-3 rounded-full px-[18px] py-2.5 text-[13.5px] font-medium whitespace-nowrap">{{ bn_digits($size) }} ×</a>
            @endforeach
            @foreach($filters['types'] as $type)
                <a href="{{ $baseUrl }}?{{ http_build_query(array_replace($queryWithout(), ['types' => array_values(array_diff($filters['types'], [$type]))])) }}"
                   class="flex-none bg-sand-3 rounded-full px-[18px] py-2.5 text-[13.5px] font-medium whitespace-nowrap">{{ $type }} ×</a>
            @endforeach
            @foreach($filters['colors'] as $color)
                <a href="{{ $baseUrl }}?{{ http_build_query(array_replace($queryWithout(), ['colors' => array_values(array_diff($filters['colors'], [$color]))])) }}"
                   class="flex-none bg-sand-3 rounded-full px-[18px] py-2.5 text-[13.5px] font-medium whitespace-nowrap">{{ $color }} ×</a>
            @endforeach
            @if($filters['in_stock'])
                <a href="{{ route('store.shop', $queryWithout(['in_stock'])) }}" class="flex-none bg-sand-3 rounded-full px-[18px] py-2.5 text-[13.5px] font-medium whitespace-nowrap">স্টকে আছে ×</a>
            @endif
            @if($filters['on_sale'])
                <a href="{{ route('store.shop', $queryWithout(['on_sale'])) }}" class="flex-none bg-sand-3 rounded-full px-[18px] py-2.5 text-[13.5px] font-medium whitespace-nowrap">ছাড় চলছে ×</a>
            @endif
        </div>
    </div>
@endsection

@section('content')
<div class="nf-fade" @keydown.escape.window="$store.shop.filtersOpen = false; $store.shop.sortOpen = false">

    {{-- Breadcrumb + title (desktop) --}}
    <div class="hidden sm:block">
        <div class="px-8 pt-5 text-[14px] text-muted">
            <a href="{{ route('store.home') }}" class="hover:text-accent">হোম</a>
            <span class="mx-1.5">/</span>
            @if($activeCategory)
                <a href="{{ route('store.shop') }}" class="hover:text-accent">সব পণ্য</a><span class="mx-1.5">/</span>
            @endif
            <span class="text-ink">{{ $title }}</span>
        </div>

        <div class="px-8 pt-3.5 pb-7 flex flex-wrap items-end justify-between gap-5">
            <div>
                <h1 class="font-display text-[64px] leading-[1.05]">{{ $title }}</h1>
                <div class="mt-1.5 text-[15px] text-muted">
                    @if($filters['q'])“{{ $filters['q'] }}” · @endif
                    {{ bn_digits($products->total()) }}টি পণ্য · {{ bn_digits($variantCount) }}টি ভ্যারিয়েন্ট
                </div>
            </div>
            <label class="inline-flex items-center gap-1.5 bg-sand rounded-full px-[22px] py-3 text-[14.5px] font-medium text-espresso hover:bg-sand-2 cursor-pointer">
                <span>সাজান:</span>
                <select x-model="$store.shop.sort" @change="$store.shop.apply()" class="appearance-none bg-transparent outline-none font-medium cursor-pointer" aria-label="সাজান">
                    @foreach($sortOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </div>

    {{-- Filter backdrop (phone / tablet sheet) --}}
    <div x-show="$store.shop.filtersOpen" x-cloak x-transition.opacity @click="$store.shop.filtersOpen = false" class="desk:hidden fixed inset-0 z-[70] bg-[#1A1413]/46"></div>

    <div class="px-5 sm:px-8 pb-16 desk:pb-20 grid desk:grid-cols-[270px_1fr] gap-6 desk:gap-10 items-start">

        {{-- ── Filters: sidebar on desktop, bottom sheet below 1100px ── --}}
        <form x-ref="filters" id="shop-filters" method="GET" action="{{ $baseUrl }}"
              :class="$store.shop.filtersOpen
                ? 'fixed inset-x-0 bottom-0 z-[75] max-h-[86vh] overflow-y-auto rounded-t-[26px] pt-3 px-5 pb-[max(20px,env(safe-area-inset-bottom))] bg-white desk:static desk:max-h-none desk:overflow-visible desk:rounded-[22px] desk:p-[26px] desk:bg-panel'
                : 'hidden desk:flex'"
              class="flex-col gap-[22px] desk:flex desk:gap-[26px] desk:bg-panel desk:rounded-[22px] desk:p-[26px] desk:sticky desk:top-24">
            @if($filters['q'])<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
            <input type="hidden" name="sort" :value="$store.shop.sort === @js($defaultSort) ? '' : $store.shop.sort">

            <div x-show="$store.shop.filtersOpen" class="desk:hidden w-10 h-1 rounded bg-[#E2DBD6] mx-auto mb-1"></div>

            <div class="flex justify-between items-baseline">
                <div class="text-[19px] desk:text-[16px] font-semibold">ফিল্টার</div>
                <a href="{{ route('store.shop') }}" class="text-[13.5px] text-accent hover:underline">সব মুছুন</a>
            </div>

            {{-- Category: checkbox list on desktop, chips on phones --}}
            <div>
                <div class="text-[12px] desk:text-[14.5px] font-medium desk:font-semibold tracking-[0.14em] desk:tracking-normal uppercase desk:normal-case text-muted desk:text-ink">ক্যাটাগরি</div>
                <div class="mt-3 hidden desk:flex flex-col gap-2.5 text-[14.5px] text-cocoa">
                    @foreach($categories as $category)
                        @php $isActive = $activeCategory?->id === $category->id; @endphp
                        <a href="{{ $isActive ? route('store.shop', $queryWithout()) : route('store.shop.category', array_merge(['category' => $category->slug], $queryWithout())) }}" class="hover:text-espresso">
                            <span @class([$boxBase, 'bg-espresso' => $isActive, 'bg-line' => ! $isActive])></span>{{ $category->name }}
                            <span class="text-muted">({{ bn_digits($category->products_count) }})</span>
                        </a>
                    @endforeach
                </div>
                <div class="mt-3 desk:hidden flex gap-2 flex-wrap">
                    @foreach($categories as $category)
                        @php $isActive = $activeCategory?->id === $category->id; @endphp
                        <a href="{{ $isActive ? route('store.shop', $queryWithout()) : route('store.shop.category', array_merge(['category' => $category->slug], $queryWithout())) }}"
                           @class(['rounded-full px-[18px] py-[11px] text-[13.5px] font-medium', 'bg-espresso text-white' => $isActive, 'bg-sand-3 text-ink' => ! $isActive])>{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>

            @if($facets['attributes_need_category'])
                <p class="-mt-2 desk:-mt-3 text-[13px] leading-relaxed text-muted bg-white/70 desk:bg-white rounded-xl px-3.5 py-2.5">
                    ধরন, রং ও সাইজ দিয়ে ফিল্টার করতে আগে একটি ক্যাটাগরি বেছে নিন।
                </p>
            @else
                @if(count($facets['types']))
                    <div>
                        <div class="text-[12px] desk:text-[14.5px] font-medium desk:font-semibold tracking-[0.14em] desk:tracking-normal uppercase desk:normal-case text-muted desk:text-ink">সুগন্ধির ধরন</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($facets['types'] as $type)
                                <label>
                                    <input type="checkbox" name="types[]" value="{{ $type }}" @change="$store.shop.apply()" class="peer sr-only" @checked(in_array($type, $filters['types'], true))>
                                    <span class="{{ $chipBase }} inline-block bg-sand-3 desk:bg-white text-cocoa peer-checked:bg-espresso peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-accent">{{ $type }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(count($facets['colors']))
                    <div>
                        <div class="text-[12px] desk:text-[14.5px] font-medium desk:font-semibold tracking-[0.14em] desk:tracking-normal uppercase desk:normal-case text-muted desk:text-ink">রং</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($facets['colors'] as $color)
                                <label>
                                    <input type="checkbox" name="colors[]" value="{{ $color }}" @change="$store.shop.apply()" class="peer sr-only" @checked(in_array($color, $filters['colors'], true))>
                                    <span class="{{ $chipBase }} inline-flex items-center gap-2 bg-sand-3 desk:bg-white text-cocoa peer-checked:bg-espresso peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-accent">
                                        <span class="w-3 h-3 rounded-full ring-1 ring-black/10" style="background:{{ color_hex($color) }}"></span>{{ $color }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($facets['sizes_need_category'])
                    <div>
                        <div class="text-[12px] desk:text-[14.5px] font-medium desk:font-semibold tracking-[0.14em] desk:tracking-normal uppercase desk:normal-case text-muted desk:text-ink">সাইজ</div>
                        <p class="mt-2 text-[13.5px] leading-relaxed text-muted">সাইজ দিয়ে ফিল্টার করতে আগে একটি ক্যাটাগরি বেছে নিন।</p>
                    </div>
                @elseif(count($facets['sizes']))
                    <div>
                        <div class="text-[12px] desk:text-[14.5px] font-medium desk:font-semibold tracking-[0.14em] desk:tracking-normal uppercase desk:normal-case text-muted desk:text-ink">সাইজ</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($facets['sizes'] as $size)
                                <label>
                                    <input type="checkbox" name="sizes[]" value="{{ $size }}" @change="$store.shop.apply()" class="peer sr-only" @checked(in_array($size, $filters['sizes'], true))>
                                    <span class="{{ $chipBase }} inline-block bg-sand-3 desk:bg-white text-cocoa peer-checked:bg-espresso peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-accent">{{ bn_digits($size) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

            @endif

            @if($hasPriceRange)
                <div>
                    <div class="text-[12px] desk:text-[14.5px] font-medium desk:font-semibold tracking-[0.14em] desk:tracking-normal uppercase desk:normal-case text-muted desk:text-ink">দাম</div>
                    <div class="mt-[18px] relative h-1.5 rounded-full bg-line">
                        <div class="absolute top-0 h-1.5 rounded-full bg-espresso" :style="`left:${$store.shop.pct($store.shop.min)}%;right:${100 - $store.shop.pct($store.shop.max)}%`"></div>
                        <input type="range" :name="$store.shop.min > $store.shop.bounds.min ? 'min' : ''" x-model.number="$store.shop.min" min="{{ $priceMin }}" max="{{ $priceMax }}" step="10"
                               @input="if ($store.shop.min > $store.shop.max) $store.shop.min = $store.shop.max" @change="$store.shop.apply()" aria-label="সর্বনিম্ন দাম" class="nf-range">
                        <input type="range" :name="$store.shop.max < $store.shop.bounds.max ? 'max' : ''" x-model.number="$store.shop.max" min="{{ $priceMin }}" max="{{ $priceMax }}" step="10"
                               @input="if ($store.shop.max < $store.shop.min) $store.shop.max = $store.shop.min" @change="$store.shop.apply()" aria-label="সর্বোচ্চ দাম" class="nf-range">
                    </div>
                    <div class="mt-3.5 flex justify-between text-[14px] text-cocoa">
                        <span x-text="'৳' + bnNumber($store.shop.min)">{{ bn_price($filters['min'] ?? $priceMin) }}</span>
                        <span x-text="'৳' + bnNumber($store.shop.max)">{{ bn_price($filters['max'] ?? $priceMax) }}</span>
                    </div>
                </div>
            @endif

            {{-- Toggles: checkbox rows on desktop, switches on phones --}}
            <div>
                <div class="hidden desk:block text-[14.5px] font-semibold text-ink">অন্যান্য</div>
                <div class="desk:mt-3 flex flex-col gap-4 desk:gap-2.5 text-[15px] desk:text-[14.5px] text-cocoa">
                    @foreach(['in_stock' => 'শুধু স্টকে আছে', 'top_rated' => 'রেটিং ৪.৫+', 'on_sale' => 'ছাড় চলছে'] as $key => $label)
                        <label class="cursor-pointer flex items-center justify-between desk:justify-start">
                            <input type="checkbox" name="{{ $key }}" value="1" @change="$store.shop.apply()" class="peer sr-only" @checked($filters[$key])>
                            <span class="order-2 desk:order-none desk:hidden nf-switch"><span></span></span>
                            <span class="hidden desk:inline {{ $boxBase }} bg-line peer-checked:bg-espresso peer-focus-visible:ring-2 peer-focus-visible:ring-accent"></span>
                            <span class="desk:hidden order-1">{{ $label }}</span>
                            <span class="hidden desk:inline">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Sheet actions (phones) --}}
            <div x-show="$store.shop.filtersOpen" class="desk:hidden flex gap-2.5 pt-1">
                <a href="{{ $baseUrl }}" class="flex-1 bg-sand-3 text-espresso rounded-full py-4 text-center text-[15px] font-semibold">রিসেট</a>
                <button type="button" @click="$store.shop.filtersOpen = false" class="flex-[2] bg-espresso text-white rounded-full py-4 text-[15px] font-semibold">
                    {{ bn_digits($products->total()) }}টি পণ্য দেখুন
                </button>
            </div>
        </form>

        {{-- ── Results ── --}}
        <div>
            @if($products->isEmpty())
                <div class="rounded-[22px] bg-panel py-20 sm:py-24 px-6 text-center">
                    <div class="font-display text-[24px] sm:text-[28px] text-espresso">কোনো পণ্য পাওয়া যায়নি</div>
                    <p class="mt-2 text-[15px] text-muted">ফিল্টার বদলে আবার চেষ্টা করুন।</p>
                    <a href="{{ route('store.shop') }}" class="inline-block mt-5 bg-espresso text-white rounded-full px-6 py-3 text-[14.5px] font-semibold">সব ফিল্টার মুছুন</a>
                </div>
            @else
                <div class="grid grid-cols-2 desk:grid-cols-3 gap-3.5 sm:gap-6">
                    @foreach($products as $product)
                        @include('storefront.partials.product-card', ['product' => $product])
                    @endforeach
                </div>

                {{ $products->onEachSide(1)->links('storefront.partials.pagination') }}
            @endif
        </div>
    </div>

    {{-- Sort sheet (phones) --}}
    <div x-show="$store.shop.sortOpen" x-cloak x-transition.opacity @click="$store.shop.sortOpen = false" class="sm:hidden fixed inset-0 z-[70] bg-[#1A1413]/46"></div>
    <div x-show="$store.shop.sortOpen" x-cloak @click.outside="$store.shop.sortOpen = false"
         x-transition:enter="transition ease-out duration-250" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
         class="sm:hidden fixed inset-x-0 bottom-0 z-[75] bg-white rounded-t-[26px] pt-3 px-5 pb-[max(20px,env(safe-area-inset-bottom))]">
        <div class="w-10 h-1 rounded bg-[#E2DBD6] mx-auto"></div>
        <div class="mt-4 text-[19px] font-semibold">সাজান</div>
        <div class="mt-3 flex flex-col">
            @foreach($sortOptions as $value => $label)
                <button type="button" @click="$store.shop.sort = @js($value); $store.shop.sortOpen = false; $store.shop.apply()"
                        class="flex items-center justify-between py-3.5 text-[15.5px] text-left border-b border-hair last:border-0">
                    <span>{{ $label }}</span>
                    <span x-show="$store.shop.sort === @js($value)" class="text-espresso">✓</span>
                </button>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('head')
<style>
    .nf-range { position: absolute; inset: -6px 0 0; width: 100%; height: 18px; margin: 0; background: none; pointer-events: none; -webkit-appearance: none; appearance: none; }
    .nf-range::-webkit-slider-thumb { pointer-events: auto; -webkit-appearance: none; width: 20px; height: 20px; border-radius: 50%; background: #fff; box-shadow: 0 2px 8px rgba(36,28,26,.3); cursor: pointer; }
    .nf-range::-moz-range-thumb { pointer-events: auto; width: 20px; height: 20px; border: 0; border-radius: 50%; background: #fff; box-shadow: 0 2px 8px rgba(36,28,26,.3); cursor: pointer; }
    .nf-range::-moz-range-track { background: none; }
</style>
@endpush

@push('head')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('shop', {
            filtersOpen: false,
            sortOpen: false,
            sort: @json($filters['sort']),
            bounds: { min: {{ $priceMin }}, max: {{ $priceMax }} },
            min: {{ $filters['min'] ?? $priceMin }},
            max: {{ $filters['max'] ?? $priceMax }},
            pct(n) { return this.bounds.max === this.bounds.min ? 0 : (n - this.bounds.min) / (this.bounds.max - this.bounds.min) * 100; },
            // Let Alpine flush bindings (e.g. the hidden sort input) before submitting.
            apply() { Alpine.nextTick(() => document.getElementById('shop-filters')?.requestSubmit()); },
        });
    });
</script>
@endpush
