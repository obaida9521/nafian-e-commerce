@extends('layouts.app')
@section('title', $product->name)
@section('no_tabbar', true)
@section('mobile_action_bar', true)

@php
    /** @var \App\Models\Product $product */
    $activeVariants = $product->variants->where('is_active', true)->values();
    $cheapest = $activeVariants->sortBy('price')->first();
    $category = $product->categories->first();
    // Gallery slides: product photos, then each variant's own photo, then the YouTube video.
    $slides = $product->getMedia('images')->map(fn ($media) => [
        'type' => 'image', 'src' => $media->getUrl(), 'thumb' => $media->getUrl('thumb'),
    ])->values();
    $variantSlides = [];
    foreach ($activeVariants as $activeVariant) {
        if ($variantMedia = $activeVariant->getFirstMedia('image')) {
            $variantSlides[$activeVariant->id] = $slides->count();
            $slides->push(['type' => 'image', 'src' => $variantMedia->getUrl(), 'thumb' => $variantMedia->getUrl('thumb')]);
        }
    }
    if ($youtubeId = $product->youtubeId()) {
        $slides->push(['type' => 'video', 'id' => $youtubeId, 'thumb' => "https://i.ytimg.com/vi/{$youtubeId}/hqdefault.jpg"]);
    }
    $totalReviews = $product->reviews->count();

    $attributeSlugs = ['size' => 'সাইজ', 'color' => 'রং'];
    $optionGroups = collect($attributeSlugs)
        ->map(fn ($label, $slug) => [
            'slug' => $slug,
            'label' => $label,
            'values' => $activeVariants->map(fn ($v) => $v->attributeValue($slug))->filter()->unique()->values()->all(),
        ])
        ->filter(fn ($group) => count($group['values']) > 0 && ! (count($group['values']) === 1 && $group['values'][0] === 'One Size'))
        ->values();

    $variantPayload = $activeVariants->map(fn ($v) => [
        'id' => $v->id,
        'options' => collect($attributeSlugs)->keys()->mapWithKeys(fn ($slug) => [$slug => $v->attributeValue($slug)])->all(),
        'price' => (float) $v->price,
        'compare' => (float) $v->compare_at_price,
        'available' => $v->available_quantity,
        'label' => $v->shortLabel(),
        'slide' => $variantSlides[$v->id] ?? null,
    ])->values();
    $firstAvailable = $variantPayload->firstWhere('available', '>', 0) ?? $variantPayload->first();

    $accordions = collect([
        ['বিস্তারিত বিবরণ', $product->description],
        ['উপাদান', $product->ingredients],
        ['ব্যবহারের নিয়ম', $product->usage_instructions],
        ['ডেলিভারি ও রিটার্ন', 'ঢাকার ভেতরে ২৪ ঘণ্টা ('.bn_price($general['delivery_inside']).'), ঢাকার বাইরে ২–৩ দিন ('.bn_price($general['delivery_outside']).')। সিল অক্ষত থাকলে ৭ দিনের মধ্যে রিটার্ন করা যাবে।'],
    ])->filter(fn ($row) => filled($row[1]))->values();
@endphp

@section('mobile_header')
@endsection

@section('content')
<div x-data="{
        variants: @js($variantPayload),
        selected: @js($firstAvailable['options'] ?? []),
        quantity: 1,
        image: @js($firstAvailable['slide'] ?? 0),
        playing: false,
        // `play` is false or the gallery ('phone' | 'desk') whose player should mount.
        show(index, play = false) { this.image = index; this.playing = play; },
        openPanel: @js($accordions->isNotEmpty() ? 0 : null),
        wish: false,
        get variant() {
            return this.variants.find(v => Object.keys(this.selected).every(k => ! this.selected[k] || v.options[k] === this.selected[k])) ?? this.variants[0];
        },
        get available() { return this.variant ? this.variant.available : 0; },
        optionAvailable(slug, value) {
            return this.variants.some(v => v.options[slug] === value && v.available > 0);
        },
        priceFor(slug, value) {
            const match = this.variants.find(v => v.options[slug] === value);
            return match ? match.price : null;
        },
        init() {
            this.$watch('quantity', q => { if (q > this.available) this.quantity = Math.max(1, this.available); });
            // Picking a variant with its own photo jumps the gallery to it (and stops the video).
            this.$watch('selected', () => { const slide = this.variant?.slide; if (slide !== null && slide !== undefined) this.show(slide); });
            // Switching between the phone and desktop gallery would leave the player running hidden.
            window.matchMedia('(min-width: 640px)').addEventListener('change', () => { this.playing = false; });
        },
     }"
     x-init="init()" class="nf-fade">

    {{-- ── Phone gallery ── --}}
    <div class="sm:hidden relative">
        <div class="relative aspect-square overflow-hidden">
            @include('storefront.partials.pdp-stage', ['slides' => $slides, 'alt' => $product->name, 'where' => 'phone'])
            <div class="absolute inset-0 -z-10" style="background:linear-gradient(160deg, color-mix(in srgb, {{ $product->tone ?? '#C2BBB0' }} 12%, #F6F3F1), color-mix(in srgb, {{ $product->tone ?? '#C2BBB0' }} 34%, #E6E0DA));"></div>

            <div class="relative flex justify-between px-5 pt-4">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('store.shop') }}" class="bg-white/90 rounded-full w-[38px] h-[38px] grid place-items-center text-[16px]" aria-label="ফিরে যান">←</a>
                <button type="button" @click="wish = ! wish; try { localStorage.setItem('nf-wish-{{ $product->id }}', wish ? '1' : '') } catch {}"
                        x-init="try { wish = !! localStorage.getItem('nf-wish-{{ $product->id }}') } catch {}"
                        class="bg-white/90 rounded-full w-[38px] h-[38px] grid place-items-center text-[15px]" :class="wish && 'text-rose'" aria-label="পছন্দের তালিকা">
                    <span x-text="wish ? '♥' : '♡'">♡</span>
                </button>
            </div>

            <template x-if="variant && variant.compare > variant.price && ! playing">
                <span class="nf-ticket absolute left-5 bottom-10 py-1.5 text-[12px] font-bold"
                      x-text="bnNumber(Math.round((variant.compare - variant.price) / variant.compare * 100)) + '% ছাড়'"></span>
            </template>

            @if($slides->count() > 1)
                <div x-show="! playing" class="absolute left-1/2 -translate-x-1/2 bottom-4 flex justify-center items-center gap-1.5 bg-black/25 backdrop-blur-sm rounded-full px-2.5 py-1.5">
                    @foreach($slides as $index => $slide)
                        @if($slide['type'] === 'video')
                            <button type="button" @click="show({{ $index }}, 'phone')" class="h-5 px-2 rounded-full bg-white/90 text-espresso text-[10.5px] font-semibold flex items-center gap-1" aria-label="ভিডিও">
                                <svg width="9" height="9" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5-11-6.5Z"/></svg>ভিডিও
                            </button>
                        @else
                            <button type="button" @click="show({{ $index }})" class="h-1.5 rounded-full transition-all" :class="image === {{ $index }} ? 'w-6 bg-white' : 'w-1.5 bg-white/55'" aria-label="ছবি {{ $index + 1 }}"></button>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="sm:hidden px-5 pt-[18px] pb-6">
        <div class="text-[12.5px] text-black">{{ $category?->name }}</div>
        <div class="mt-1 flex items-start justify-between gap-3">
            <h1 class="font-sans font-semibold text-[24px] leading-snug text-black">{{ $product->name }}</h1>
            <span class="mt-1 flex-none rounded-full px-[11px] py-1 text-[12px] font-semibold whitespace-nowrap" :class="available > 0 ? 'bg-accent-soft text-accent' : 'bg-rose-soft text-rose'"
                  x-text="available > 0 ? 'স্টকে আছে' : 'স্টকে নেই'"></span>
        </div>
        <div class="mt-2 flex items-center justify-between gap-3">
            <div class="flex items-baseline gap-2 flex-wrap min-w-0">
                <span class="text-[24px] font-semibold text-espresso" x-text="'৳' + bnNumber(variant?.price ?? 0)"></span>
                <template x-if="variant && variant.compare > variant.price">
                    <span class="text-[15px] text-muted line-through" x-text="'৳' + bnNumber(variant.compare)"></span>
                </template>
                <template x-if="variant && variant.compare > variant.price">
                    <span class="bg-clay-soft text-espresso rounded-full px-2.5 py-1 text-[12px] font-semibold"
                          x-text="'সাশ্রয় ৳' + bnNumber(variant.compare - variant.price)"></span>
                </template>
            </div>
            @if($product->rating)
                <a href="#reviews-mobile" class="flex-none inline-flex items-center gap-1.5 rounded-full bg-sand-3 px-2.5 py-1 text-[12px] font-semibold text-black whitespace-nowrap">
                    <x-ui.stars :rating="$product->rating" class="text-[11px]" />
                    {{ bn_digits(number_format((float) $product->rating, 1)) }}
                    <span class="font-normal">({{ bn_digits($totalReviews) }})</span>
                </a>
            @endif
        </div>
        @if($product->short_description)
            <p class="mt-3.5 text-[15.5px] leading-[1.85] text-black">{{ $product->short_description }}</p>
        @endif

        @foreach($optionGroups as $group)
            <div class="mt-5">
                <div class="text-[12px] font-medium tracking-[0.14em] uppercase text-muted">{{ $group['label'] }}</div>
                <div class="mt-2.5 flex gap-2 flex-wrap">
                    @include('storefront.partials.variant-chips', ['group' => $group])
                </div>
            </div>
        @endforeach

        <div class="mt-[18px] bg-panel-2 rounded-2xl p-4 flex flex-col gap-2.5 text-[13.5px] text-black">
            <div class="flex justify-between"><span>ঢাকার ভেতরে</span><span class="text-black">২৪ ঘণ্টা · {{ bn_price($general['delivery_inside']) }}</span></div>
            <div class="flex justify-between"><span>ঢাকার বাইরে</span><span class="text-black">২–৩ দিন · {{ bn_price($general['delivery_outside']) }}</span></div>
            <div class="flex justify-between"><span>ক্যাশ অন ডেলিভারি</span><span class="text-black">সারাদেশে</span></div>
        </div>

        <div class="mt-[18px] flex flex-col">
            @foreach($accordions as $index => [$title, $body])
                <button type="button" @click="openPanel = openPanel === {{ $index }} ? null : {{ $index }}" class="py-3.5 flex justify-between text-[15px] font-medium text-left">
                    {{ $title }} <span class="text-muted" x-text="openPanel === {{ $index }} ? '−' : '+'">+</span>
                </button>
                <p x-show="openPanel === {{ $index }}" x-collapse x-cloak class="pb-3.5 text-[15px] leading-[1.85] text-black">{{ $body }}</p>
                <div class="nf-line"></div>
            @endforeach
        </div>

        {{-- Reviews (phone): overview, latest few, then the full reviews page --}}
        <section id="reviews-mobile" class="mt-8 scroll-mt-4">
            <div class="flex items-baseline justify-between">
                <h2 class="font-display text-[24px]">রিভিউ <span class="font-sans text-[15px] text-muted">({{ bn_digits($totalReviews) }})</span></h2>
                @if($totalReviews > 0)
                    <a href="{{ route('store.product.reviews', $product->slug) }}" class="text-[14px] font-medium text-accent">সব দেখুন →</a>
                @endif
            </div>

            <div class="mt-3.5">
                @include('storefront.partials.review-summary')
            </div>

            <div class="mt-3">
                @include('storefront.partials.review-list', ['reviews' => $previewReviews, 'clamp' => true])
            </div>

            <div class="mt-3.5 flex gap-2.5">
                @if($totalReviews > $previewReviews->count())
                    <a href="{{ route('store.product.reviews', $product->slug) }}" class="flex-1 text-center bg-espresso text-white rounded-xl py-3.5 text-[14.5px] font-semibold">
                        সব {{ bn_digits($totalReviews) }}টি রিভিউ দেখুন
                    </a>
                @endif
                <a href="{{ route('store.product.reviews', $product->slug) }}#write-review" class="flex-1 text-center bg-sand-3 text-espresso rounded-xl py-3.5 text-[14.5px] font-semibold">
                    {{ $userReview ? 'রিভিউ সম্পাদনা' : 'রিভিউ লিখুন' }}
                </a>
            </div>
        </section>

        {{-- Related (phone): swipeable rail, edge to edge --}}
        @if($related->isNotEmpty())
            <section class="mt-9">
                <div class="flex items-baseline justify-between">
                    <h2 class="font-display text-[24px]">সাথে মানানসই</h2>
                    @if($category)
                        <a href="{{ route('store.shop.category', $category->slug) }}" class="text-[14px] font-medium text-accent">সব দেখুন →</a>
                    @endif
                </div>
                <div class="mt-3.5 -mx-5 px-5 scroll-px-5 nf-rail gap-3 pb-1">
                    @foreach($related as $relatedProduct)
                        @include('storefront.partials.product-card', ['product' => $relatedProduct, 'style' => 'rail'])
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- ── Desktop ── --}}
    <div class="hidden sm:block">
        <div class="px-8 pt-5 pb-[22px] text-[14px] text-muted">
            <a href="{{ route('store.home') }}" class="hover:text-accent">হোম</a><span class="mx-1.5">/</span>
            @if($category)<a href="{{ route('store.shop.category', $category->slug) }}" class="hover:text-accent">{{ $category->name }}</a><span class="mx-1.5">/</span>@endif
            <span class="text-ink">{{ $product->name }}</span>
        </div>

        <div class="px-8 grid desk:grid-cols-2 gap-8 desk:gap-14 items-start">
            {{-- Gallery --}}
            <div class="grid grid-cols-[88px_1fr] gap-3.5 max-[1100px]:grid-cols-1">
                <div class="flex flex-col gap-3 p-1 -m-1 max-[1100px]:flex-row max-[1100px]:order-2 max-[1100px]:overflow-x-auto">
                    @forelse($slides as $index => $slide)
                        <button type="button" @click="show({{ $index }}, {{ $slide['type'] === 'video' ? "'desk'" : 'false' }})"
                                class="relative flex-none aspect-square max-[1100px]:w-[84px] rounded-[14px] overflow-hidden bg-sand ring-offset-2 ring-offset-white transition"
                                :class="image === {{ $index }} ? 'ring-2 ring-dust opacity-100' : 'opacity-55 hover:opacity-100'"
                                :aria-current="image === {{ $index }}"
                                aria-label="{{ $slide['type'] === 'video' ? 'ভিডিও চালান' : 'ছবি '.($index + 1) }}">
                            <img src="{{ $slide['thumb'] }}" alt="" loading="lazy" onerror="this.remove()" class="w-full h-full object-cover">
                            @if($slide['type'] === 'video')
                                <span class="absolute inset-0 grid place-items-center"><span class="w-8 h-8 rounded-full bg-white/90 text-espresso grid place-items-center"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5-11-6.5Z"/></svg></span></span>
                            @endif
                        </button>
                    @empty
                        @for($i = 0; $i < 3; $i++)
                            <div class="aspect-square max-[1100px]:w-[84px] rounded-[14px]" style="background:linear-gradient(160deg,#F3F0ED,#E9E4DF);"></div>
                        @endfor
                    @endforelse
                </div>
                <div class="relative rounded-[24px] overflow-hidden aspect-square shadow-[0_18px_44px_-30px_rgba(36,28,26,.6)]"
                     style="background:linear-gradient(160deg, color-mix(in srgb, {{ $product->tone ?? '#C2BBB0' }} 12%, #F4F1EE), color-mix(in srgb, {{ $product->tone ?? '#C2BBB0' }} 32%, #E7E1DC));">
                    @include('storefront.partials.pdp-stage', ['slides' => $slides, 'alt' => $product->name, 'where' => 'desk'])
                    <template x-if="variant && variant.compare > variant.price && ! playing">
                        <span class="nf-ticket absolute left-[18px] top-[18px] py-[7px] text-[12.5px] font-bold"
                              x-text="bnNumber(Math.round((variant.compare - variant.price) / variant.compare * 100)) + '% ছাড়'"></span>
                    </template>
                </div>
            </div>

            {{-- Details --}}
            <div>
                <div class="text-[14px] text-black">NAFIAN{{ $category ? ' · '.$category->name : '' }}</div>
                <h1 class="mt-2 font-sans font-semibold text-[36px] desk:text-[46px] leading-[1.25] text-black">{{ $product->name }}</h1>

                <div class="mt-2.5 flex items-center gap-2.5 text-[14.5px] text-black flex-wrap">
                    @if($product->rating)
                        <x-ui.stars :rating="$product->rating" />
                        {{ bn_digits(number_format((float) $product->rating, 1)) }}
                        <span class="text-black">· {{ bn_digits($totalReviews) }} রিভিউ</span>
                    @endif
                    <span class="rounded-full px-3 py-[5px] text-[13px] font-medium" :class="available > 0 ? 'bg-accent-soft text-accent' : 'bg-rose-soft text-rose'"
                          x-text="available > 0 ? 'স্টকে আছে' : 'স্টকে নেই'"></span>
                </div>

                <div class="mt-5 flex items-baseline gap-3 flex-wrap">
                    <span class="text-[34px] font-semibold text-espresso" x-text="'৳' + bnNumber(variant?.price ?? 0)"></span>
                    <template x-if="variant && variant.compare > variant.price">
                        <span class="text-[19px] text-muted line-through" x-text="'৳' + bnNumber(variant.compare)"></span>
                    </template>
                    <template x-if="variant && variant.compare > variant.price">
                        <span class="bg-clay-soft text-espresso rounded-full px-3.5 py-1.5 text-[13px] font-semibold"
                              x-text="'সাশ্রয় ৳' + bnNumber(variant.compare - variant.price)"></span>
                    </template>
                </div>

                @if($product->short_description)
                    <p class="mt-[18px] text-[16.5px] leading-[1.9] text-black">{{ $product->short_description }}</p>
                @endif

                @foreach($optionGroups as $group)
                    <div class="mt-6">
                        <div class="text-[14.5px] font-semibold">{{ $group['label'] }} নির্বাচন করুন</div>
                        <div class="mt-3 flex gap-2 flex-wrap">
                            @include('storefront.partials.variant-chips', ['group' => $group])
                        </div>
                    </div>
                @endforeach

                <div class="mt-6 flex gap-3 items-center flex-wrap">
                    <div class="flex items-center gap-[18px] bg-sand rounded-xl px-5 py-3">
                        <button type="button" @click="quantity = Math.max(1, quantity - 1)" class="text-[20px] text-muted leading-none" aria-label="কমান">−</button>
                        <span class="text-[16px] font-semibold min-w-[20px] text-center" x-text="bnNumber(quantity)"></span>
                        <button type="button" @click="quantity = Math.min(available, quantity + 1)" :disabled="quantity >= available" class="text-[20px] text-espresso leading-none disabled:opacity-30" aria-label="বাড়ান">+</button>
                    </div>

                    <template x-if="available > 0">
                        <div class="flex gap-3 flex-wrap flex-1">
                            <form method="POST" action="{{ route('store.cart.store') }}" class="js-cart-form flex-1 min-w-[200px]">
                                @csrf
                                <input type="hidden" name="variant_id" :value="variant?.id">
                                <input type="hidden" name="quantity" :value="quantity">
                                <button class="w-full bg-espresso text-white rounded-xl px-8 py-[17px] text-[15.5px] font-semibold hover:bg-ink">ব্যাগে যোগ করুন</button>
                            </form>
                            <form method="POST" action="{{ route('store.cart.store') }}" class="js-cart-form flex-1 min-w-[160px]">
                                @csrf
                                <input type="hidden" name="variant_id" :value="variant?.id">
                                <input type="hidden" name="quantity" :value="quantity">
                                <input type="hidden" name="buy_now" value="1">
                                <button class="w-full bg-accent-soft text-accent rounded-xl px-8 py-[17px] text-[15.5px] font-semibold">এখনই কিনুন</button>
                            </form>
                        </div>
                    </template>

                    <template x-if="available < 1">
                        <form method="POST" action="{{ route('store.product.restock', $product->slug) }}" class="flex-1 min-w-[260px] flex gap-2.5"
                              x-data="{ busy: false }"
                              @submit.prevent="
                                busy = true;
                                fetch($el.action, { method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData($el) })
                                    .then(async (r) => { const d = await r.json(); window.nfFlush?.(d.analytics); $dispatch('cart:toast', { type: r.ok ? 'success' : 'error', msg: d.message }); if (r.ok) $el.reset(); })
                                    .catch(() => $el.submit())
                                    .finally(() => busy = false);
                              ">
                            @csrf
                            <input name="contact" required maxlength="120" placeholder="ইমেইল বা মোবাইল" class="flex-1 bg-sand rounded-xl px-5 py-[17px] text-[14.5px] outline-none">
                            <button :disabled="busy" class="bg-espresso text-white rounded-xl px-7 text-[14.5px] font-semibold disabled:opacity-60">স্টকে এলে জানান</button>
                        </form>
                    </template>
                </div>

                <div class="mt-[26px] bg-panel rounded-[20px] px-6 py-[22px] flex flex-col gap-3 text-[14.5px] text-black">
                    <div class="flex justify-between"><span>ঢাকার ভেতরে</span><span class="text-black">২৪ ঘণ্টা · {{ bn_price($general['delivery_inside']) }}</span></div>
                    <div class="flex justify-between"><span>ঢাকার বাইরে</span><span class="text-black">২–৩ দিন · {{ bn_price($general['delivery_outside']) }}</span></div>
                    <div class="flex justify-between"><span>ক্যাশ অন ডেলিভারি</span><span class="text-black">সারাদেশে</span></div>
                    <div class="flex justify-between"><span>রিটার্ন</span><span class="text-black">৭ দিন, সিল অক্ষত থাকলে</span></div>
                </div>

                <div class="mt-6 flex flex-col">
                    @foreach($accordions as $index => [$title, $body])
                        @unless($loop->first)<div class="nf-line"></div>@endunless
                        <button type="button" @click="openPanel = openPanel === {{ $index }} ? null : {{ $index }}" class="py-4 flex justify-between text-[15px] font-medium text-left">
                            {{ $title }} <span class="text-muted" x-text="openPanel === {{ $index }} ? '−' : '+'">+</span>
                        </button>
                        <p x-show="openPanel === {{ $index }}" x-collapse x-cloak class="pb-4 text-[15.5px] leading-[1.9] text-black">{{ $body }}</p>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Reviews --}}
        <div id="reviews" class="px-8 pt-16 scroll-mt-24">
            <h2 class="font-display text-[30px] mb-6">রিভিউ</h2>
            <div class="grid desk:grid-cols-[300px_1fr] gap-8 desk:gap-12 items-start">
                <div class="flex flex-col gap-4 desk:sticky desk:top-24">
                    @include('storefront.partials.review-summary')
                    <a href="#write-review" class="block text-center bg-sand-3 text-espresso rounded-xl py-3.5 text-[14.5px] font-semibold hover:bg-sand-2">
                        {{ $userReview ? 'আপনার রিভিউ সম্পাদনা করুন' : 'রিভিউ লিখুন' }}
                    </a>
                </div>

                <div>
                    @include('storefront.partials.review-list', ['reviews' => $previewReviews])

                    @if($totalReviews > $previewReviews->count())
                        <a href="{{ route('store.product.reviews', $product->slug) }}" class="mt-4 block text-center bg-espresso text-white rounded-xl py-3.5 text-[14.5px] font-semibold hover:bg-ink">
                            সব {{ bn_digits($totalReviews) }}টি রিভিউ দেখুন
                        </a>
                    @endif

                    <div class="mt-5">
                        @include('storefront.partials.review-form')
                    </div>
                </div>
            </div>
        </div>

        {{-- Related --}}
        @if($related->isNotEmpty())
            <div class="px-8 pt-16 pb-20">
                <h2 class="font-display text-[30px] mb-6">সাথে মানানসই</h2>
                <div class="grid grid-cols-2 desk:grid-cols-4 gap-[22px]">
                    @foreach($related as $relatedProduct)
                        @include('storefront.partials.product-card', ['product' => $relatedProduct, 'style' => 'mini'])
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Sticky buy bar (phones) --}}
    <div class="sm:hidden fixed inset-x-0 bottom-0 z-50 bg-white px-5 pt-3 pb-[max(12px,env(safe-area-inset-bottom))] flex gap-3 items-center shadow-[0_-8px_22px_-20px_rgba(36,28,26,.9)]">
        <div>
            <div class="text-[18px] font-semibold text-espresso" x-text="'৳' + bnNumber(variant?.price ?? 0)"></div>
            <div class="text-[12px] text-muted" x-text="variant ? variant.label : ''"></div>
        </div>
        <template x-if="available > 0">
            <form method="POST" action="{{ route('store.cart.store') }}" class="js-cart-form flex-1">
                @csrf
                <input type="hidden" name="variant_id" :value="variant?.id">
                <button class="w-full bg-espresso text-white rounded-xl py-[15px] text-[15px] font-semibold">ব্যাগে যোগ করুন</button>
            </form>
        </template>
        <template x-if="available < 1">
            <a href="#" @click.prevent="openPanel = null; $dispatch('cart:toast', { type: 'error', msg: 'পণ্যটি এখন স্টকে নেই।' })"
               class="flex-1 text-center bg-sand text-muted rounded-xl py-[15px] text-[15px] font-semibold">স্টকে নেই</a>
        </template>
    </div>
</div>
@endsection
