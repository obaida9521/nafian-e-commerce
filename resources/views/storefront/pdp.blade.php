@extends('layouts.app')
@section('title', $product->name)

@section('content')
@php
    $minPrice = $product->variants->min('price');
    $compareAt = $product->variants->max('compare_at_price');
    $inStock = $product->total_stock > 0;
    $cat = $product->categories->first()?->name;
@endphp
<div class="max-w-[1180px] mx-auto px-4 sm:px-6 pt-6 pb-[72px] nf-fade"
     x-data="{
        color: @js($colorOptions[0] ?? null),
        size: @js($sizeOptions[0] ?? null),
        qty: 1,
        tab: '{{ $errors->any() ? 'reviews' : 'desc' }}'
     }">
    <a href="{{ route('store.shop') }}" class="text-[13px] text-gray-500 mb-5 inline-flex items-center gap-1.5">← Back to shop</a>

    <div class="grid md:grid-cols-2 gap-8 md:gap-14 items-start">
        {{-- Gallery --}}
        @php $images = $product->getMedia('images'); @endphp
        <div>
            @if($images->isNotEmpty())
                <div x-data="{
                        active: @js($images->first()->getUrl()),
                        swapping: false,
                        change(url) {
                            if (url === this.active || this.swapping) { return; }
                            this.swapping = true;
                            const img = new Image();
                            const show = () => {
                                this.active = url;
                                requestAnimationFrame(() => { this.swapping = false; });
                            };
                            img.onload = show;
                            img.onerror = show;
                            img.src = url;
                        }
                     }">
                    <div class="relative rounded-[14px] overflow-hidden aspect-[4/5] bg-[#EFE6D6]">
                        <img :src="active" alt="{{ $product->name }}"
                             class="absolute inset-0 w-full h-full object-cover transition-opacity duration-500 ease-out"
                             :class="swapping ? 'opacity-0' : 'opacity-100'">
                    </div>
                    @if($images->count() > 1)
                        <div class="grid grid-cols-4 gap-3 mt-3">
                            @foreach($images as $media)
                                <button type="button" @click="change(@js($media->getUrl()))"
                                        class="aspect-square rounded-[9px] overflow-hidden transition duration-300 hover:scale-[1.04]"
                                        :style="active===@js($media->getUrl()) ? 'box-shadow:0 0 0 2px #691d2a' : 'box-shadow:0 0 0 1px #EADBC4'">
                                    <img src="{{ $media->getUrl('thumb') ?: $media->getUrl() }}" alt="" class="w-full h-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="relative rounded-[14px] overflow-hidden aspect-[4/5]" style="background:linear-gradient(155deg,{{ $product->tone ?? '#C9B49A' }},{{ $product->tone2 ?? '#A98F6E' }});">
                    <div class="absolute inset-0" style="background-image:repeating-linear-gradient(135deg,rgba(255,255,255,0.05) 0 2px,transparent 2px 20px);"></div>
                    <div class="absolute left-4 bottom-3.5 font-mono text-[10px] text-[#691d2a]/50 bg-[#FAFAF8]/70 px-2 py-1 rounded-[5px]">PRODUCT · 4:5</div>
                </div>
            @endif
        </div>

        {{-- Info --}}
        <div>
            <div class="text-xs tracking-[0.1em] uppercase text-gray-400 font-semibold mb-2.5">{{ $cat }}</div>
            <h1 class="text-[28px] font-semibold tracking-tight mb-3 leading-tight">{{ $product->name }}</h1>
            <div class="flex items-center gap-3 mb-4.5">
                <span class="text-[#D4A853] text-[15px]">{{ str_repeat('★', (int) round($product->rating)) }}{{ str_repeat('☆', 5 - (int) round($product->rating)) }}</span>
                <span class="text-[13px] text-gray-400">{{ $product->reviews_count }} reviews</span>
            </div>
            <div class="flex items-center gap-3 mb-2">
                <div class="text-[26px] font-semibold">{{ shop_price($minPrice) }}</div>
                @if($compareAt && $compareAt > $minPrice)
                    <div class="text-gray-400 line-through">{{ shop_price($compareAt) }}</div>
                @endif
            </div>
            <div class="text-[13px] font-medium mb-6.5 {{ $inStock ? 'text-green-600' : 'text-red-500' }}">
                {{ $inStock ? ($product->total_stock <= config('shop.low_stock_threshold') ? 'Low stock — only '.$product->total_stock.' left' : 'In stock, ready to ship') : 'Out of stock' }}
            </div>

            @if(!empty($colorOptions))
                <div class="text-[13px] font-semibold mb-2.5">Colour</div>
                <div class="flex gap-2.5 mb-6">
                    @foreach($colorOptions as $name)
                        <button @click="color=@js($name)" title="{{ $name }}" class="w-8 h-8 rounded-full"
                                :style="`background:{{ color_hex($name) }};box-shadow:0 0 0 2px #fff, 0 0 0 3.5px ${color===@js($name) ? '#691d2a' : '#E3D8C4'}`"></button>
                    @endforeach
                </div>
            @endif

            @if(!empty($sizeOptions))
                <div class="text-[13px] font-semibold mb-2.5">Size</div>
                <div class="flex flex-wrap gap-2.5 mb-7">
                    @foreach($sizeOptions as $name)
                        <button @click="size=@js($name)" class="min-w-[46px] h-[42px] px-3 rounded-[7px] border text-sm font-medium"
                                :style="`border-color:${size===@js($name) ? '#691d2a' : '#EADBC4'};background:${size===@js($name) ? '#691d2a' : '#fff'};color:${size===@js($name) ? '#fff' : '#374151'}`">{{ $name }}</button>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('store.cart.store') }}" class="js-cart-form flex gap-3 items-center">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="color" :value="color">
                <input type="hidden" name="size" :value="size">
                <input type="hidden" name="quantity" :value="qty">
                <div class="flex items-center border border-[#EADBC4] rounded-lg overflow-hidden h-[50px]">
                    <button type="button" @click="qty = Math.max(1, qty-1)" class="w-[42px] h-[50px] text-lg text-gray-700">−</button>
                    <span class="w-9 text-center font-semibold" x-text="qty"></span>
                    <button type="button" @click="qty++" class="w-[42px] h-[50px] text-lg text-gray-700">+</button>
                </div>
                <button type="submit" {{ $inStock ? '' : 'disabled' }} class="flex-1 h-[50px] rounded-lg text-white text-[15px] font-semibold {{ $inStock ? 'bg-[#691d2a] hover:bg-[#4d141e]' : 'bg-gray-300 cursor-not-allowed' }}">
                    {{ $inStock ? 'Add to bag · '.shop_price($minPrice) : 'Sold out' }}
                </button>
            </form>

            {{-- Tabs --}}
            <div class="mt-10 border-b border-[#EADBC4] flex gap-7">
                @foreach(['desc'=>'Description','details'=>'Details','reviews'=>'Reviews'] as $key=>$label)
                    <button @click="tab=@js($key)" class="pb-3 text-sm font-semibold -mb-px"
                            :style="`color:${tab===@js($key) ? '#111' : '#9CA3AF'};border-bottom:2px solid ${tab===@js($key) ? '#691d2a' : 'transparent'}`">{{ $label }}</button>
                @endforeach
            </div>
            <div class="pt-5">
                <div x-show="tab==='desc'"><p class="text-[15px] leading-relaxed text-gray-700">{{ $product->description }}</p></div>
                <div x-show="tab==='details'" x-cloak class="flex flex-col gap-2.5">
                    @foreach(['Crafted in small batches','Premium leather, wool & silk','Carbon-neutral delivery','30-day easy returns'] as $d)
                        <div class="text-sm text-gray-700 flex gap-2.5"><span class="text-[#D4A853]">•</span>{{ $d }}</div>
                    @endforeach
                </div>
                <div x-show="tab==='reviews'" x-cloak id="reviews">
                    {{-- Summary --}}
                    <div class="flex items-center gap-4 mb-6">
                        <div class="text-[40px] font-semibold leading-none">{{ $product->rating ? number_format($product->rating, 1) : '—' }}</div>
                        <div>
                            <div class="text-[#D4A853] text-[15px]">{{ str_repeat('★', (int) round($product->rating)) }}{{ str_repeat('☆', 5 - (int) round($product->rating)) }}</div>
                            <div class="text-[13px] text-gray-400 mt-0.5">{{ $product->reviews_count }} {{ Str::plural('review', $product->reviews_count) }}</div>
                        </div>
                    </div>

                    {{-- Write / edit review --}}
                    @auth('web')
                        <form method="POST" action="{{ route('store.product.review', $product->slug) }}"
                              x-data="{ rating: {{ $userReview->rating ?? 0 }}, hover: 0 }"
                              class="border border-[#EADBC4] rounded-xl p-5 mb-7 bg-[#FBF4E8]">
                            @csrf
                            <div class="font-semibold text-sm mb-3">{{ $userReview ? 'Update your review' : 'Write a review' }}</div>
                            <div class="flex gap-1 mb-4" @mouseleave="hover=0">
                                @for($i = 1; $i <= 5; $i++)
                                    <button type="button" @click="rating={{ $i }}" @mouseenter="hover={{ $i }}"
                                            class="text-2xl leading-none transition"
                                            :class="(hover || rating) >= {{ $i }} ? 'text-[#D4A853]' : 'text-gray-300'">★</button>
                                @endfor
                            </div>
                            <input type="hidden" name="rating" :value="rating">
                            @error('rating')<div class="text-xs text-red-500 mb-2">{{ $message }}</div>@enderror
                            <input name="title" maxlength="150" value="{{ old('title', $userReview->title ?? '') }}" placeholder="Title (optional)"
                                   class="w-full h-11 border border-[#EADBC4] rounded-lg px-3.5 text-sm bg-white outline-none focus:border-[#691d2a] mb-2.5">
                            <textarea name="body" rows="3" maxlength="2000" placeholder="Share what you think about this piece…"
                                      class="w-full border border-[#EADBC4] rounded-lg px-3.5 py-2.5 text-sm bg-white outline-none focus:border-[#691d2a] mb-3">{{ old('body', $userReview->body ?? '') }}</textarea>
                            <button type="submit" :disabled="!rating"
                                    class="h-11 px-6 rounded-lg bg-[#691d2a] hover:bg-[#4d141e] text-white text-sm font-semibold disabled:opacity-40 disabled:cursor-not-allowed">
                                {{ $userReview ? 'Update review' : 'Post review' }}
                            </button>
                        </form>
                    @else
                        <div class="border border-[#EADBC4] rounded-xl p-5 mb-7 text-sm text-gray-600">
                            <a href="{{ route('login') }}" class="text-[#691d2a] font-semibold underline">Log in</a> to write a review.
                        </div>
                    @endauth

                    {{-- Review list --}}
                    @forelse($product->reviews as $review)
                        <div class="border-b border-[#EFE2CE] py-4 last:border-0">
                            <div class="flex items-center justify-between mb-1">
                                <div class="font-semibold text-sm">{{ $review->user->name }}</div>
                                <div class="text-[12px] text-gray-400">{{ $review->created_at->format('M j, Y') }}</div>
                            </div>
                            <div class="text-[#D4A853] text-[13px] mb-1.5">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</div>
                            @if($review->title)<div class="font-semibold text-sm mb-0.5">{{ $review->title }}</div>@endif
                            @if($review->body)<p class="text-sm text-gray-600 leading-relaxed">{{ $review->body }}</p>@endif
                        </div>
                    @empty
                        <div class="text-sm text-gray-400">No reviews yet. Be the first to share your thoughts.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Related --}}
    @if($related->isNotEmpty())
    <div class="mt-16">
        <h2 class="text-2xl font-semibold tracking-tight mb-5.5">You may also like</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach($related as $p)
                <a href="{{ route('store.product', $p->slug) }}">
                    @php $pImg = $p->getFirstMediaUrl('images', 'thumb') ?: $p->getFirstMediaUrl('images'); @endphp
                    <div class="relative rounded-[11px] overflow-hidden aspect-[4/5]" style="background:linear-gradient(155deg,{{ $p->tone ?? '#C2BBB0' }},{{ $p->tone2 ?? '#A39B8E' }});">
                        @if($pImg)<img src="{{ $pImg }}" alt="{{ $p->name }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover">@endif
                    </div>
                    <div class="pt-3"><div class="font-semibold text-[15px] mb-0.5">{{ $p->name }}</div><div class="text-[15px] font-semibold">{{ shop_price($p->variants->min('price')) }}</div></div>
                </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
