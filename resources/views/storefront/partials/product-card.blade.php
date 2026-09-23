@php
    /** @var \App\Models\Product $product */
    $style = $style ?? 'grid';              // grid | rail | mini
    $reviewWord = $reviewWord ?? false;     // home shows "(১২৬ রিভিউ)", the shop just "(১২৬)"

    $variants = $product->variants->where('is_active', true)->values();
    $cheapest = $variants->sortBy('price')->first();
    $price = (float) ($cheapest?->price ?? 0);
    $comparePrice = (float) ($cheapest?->compare_at_price ?? 0);
    $discount = $comparePrice > $price && $comparePrice > 0 ? (int) round(($comparePrice - $price) / $comparePrice * 100) : 0;
    $manyPrices = $variants->pluck('price')->unique()->count() > 1;

    $inStock = $variants->contains(fn ($v) => $v->available_quantity > 0);
    $hasMultiple = $variants->count() > 1;
    $firstInStock = $variants->firstWhere(fn ($v) => $v->available_quantity > 0);

    $categoryName = $product->categories->first()?->name;
    $sizeLabel = $cheapest && $cheapest->shortLabel() !== $cheapest->sku ? bn_digits($cheapest->shortLabel()) : null;
    $meta = collect([$categoryName, $sizeLabel])->filter()->implode(' · ');
    $url = route('store.product', $product->slug);
@endphp

@if($style === 'mini')
    {{-- Related / deal tile (Nafian Product · সাথে মানানসই, Nafian Offers · ছাড় চলছে) --}}
    <a href="{{ $url }}" class="block rounded-[20px] overflow-hidden bg-white nf-shadow group">
        <x-ui.product-image :product="$product" hover class="aspect-[1/1.05]">
            @if($discount > 0)
                <span class="absolute left-3.5 top-3.5 bg-espresso text-white rounded-full px-[13px] py-1.5 text-[12px] font-semibold">{{ bn_digits($discount) }}% ছাড়</span>
            @elseif(! $inStock)
                <span class="absolute inset-0 grid place-items-center"><span class="bg-[#241C1A]/70 text-white rounded-full px-[18px] py-[9px] text-[13px] font-medium">স্টকে নেই</span></span>
            @endif
        </x-ui.product-image>
        <div class="px-[18px] pt-4 pb-5">
            <div class="text-[17.5px] font-semibold group-hover:text-accent">{{ $product->name }}</div>
            @if($meta)<div class="mt-[3px] text-[13.5px] text-muted">{{ $meta }}</div>@endif
            <div class="mt-2.5 flex gap-2 items-baseline">
                <span class="text-[18px] font-semibold text-espresso">{{ bn_price($price) }}</span>
                @if($discount > 0)<span class="text-[14px] text-muted line-through">{{ bn_price($comparePrice) }}</span>@endif
            </div>
        </div>
    </a>

@elseif($style === 'rail')
    {{-- Phone home rail card (Nafian Mobile · হোম) --}}
    <a href="{{ $url }}" class="block w-[168px] flex-none">
        <x-ui.product-image :product="$product" hover class="h-40 rounded-[18px]">
            @if($discount > 0)
                <span class="absolute left-2.5 top-2.5 bg-espresso text-white rounded-full px-[11px] py-[5px] text-[11px] font-semibold">{{ bn_digits($discount) }}%</span>
            @elseif(! $inStock)
                <span class="absolute left-2.5 top-2.5 bg-[#241C1A]/70 text-white rounded-full px-[11px] py-[5px] text-[11px] font-medium">স্টকে নেই</span>
            @endif
        </x-ui.product-image>
        <div class="mt-2.5 text-[15.5px] font-semibold truncate">{{ $product->name }}</div>
        @if($meta)<div class="mt-0.5 text-[12.5px] text-muted truncate">{{ $meta }}</div>@endif
        <div class="mt-1.5 flex gap-[7px] items-baseline">
            <span class="text-[16px] font-semibold text-espresso">{{ bn_price($price) }}</span>
            @if($discount > 0)<span class="text-[12.5px] text-muted line-through">{{ bn_price($comparePrice) }}</span>@endif
        </div>
    </a>

@else
    {{-- Shop / home grid card: full card on desktop, compact tile on phones --}}
    <div x-data="{ pick: false, notify: false }"
         class="group relative flex flex-col sm:rounded-[20px] sm:overflow-hidden sm:bg-white sm:nf-shadow">
        <a href="{{ $url }}" class="block">
            <x-ui.product-image :product="$product" hover class="h-44 rounded-[18px] sm:h-auto sm:rounded-none sm:aspect-[1/1.1]">
                @if(! $inStock)
                    <span class="absolute inset-0 grid place-items-center">
                        <span class="bg-[#241C1A]/70 text-white rounded-full px-[18px] py-[9px] text-[12.5px] sm:text-[13.5px] font-medium">স্টকে নেই</span>
                    </span>
                @elseif($discount > 0)
                    <span class="absolute left-2.5 top-2.5 sm:left-3.5 sm:top-3.5 bg-espresso text-white rounded-full px-[11px] py-[5px] sm:px-[13px] sm:py-1.5 text-[11px] sm:text-[12px] font-semibold">
                        {{ bn_digits($discount) }}%<span class="hidden sm:inline"> ছাড়</span>
                    </span>
                @elseif($product->badge)
                    <span class="absolute left-2.5 top-2.5 sm:left-3.5 sm:top-3.5 bg-accent text-white rounded-full px-[11px] py-[5px] sm:px-[13px] sm:py-1.5 text-[11px] sm:text-[12px] font-semibold">{{ $product->badge }}</span>
                @endif
            </x-ui.product-image>
        </a>

        <div class="pt-2.5 sm:px-5 sm:pt-[18px] sm:pb-[22px] flex flex-col flex-1">
            @if($meta)
                <div class="hidden sm:block text-[13px] text-muted">{{ $meta }}</div>
            @endif
            <a href="{{ $url }}" @class(['text-[15px] sm:text-[19px] font-semibold leading-snug sm:mt-1', 'text-muted' => ! $inStock])>{{ $product->name }}</a>
            @if($sizeLabel)<div class="sm:hidden mt-0.5 text-[12.5px] text-muted">{{ $sizeLabel }}</div>@endif
            @if($product->short_description)
                <div class="hidden sm:block mt-1 text-[14px] text-muted line-clamp-1">{{ $product->short_description }}</div>
            @endif

            <div class="mt-[5px] sm:mt-3 flex gap-1.5 sm:gap-2.5 items-baseline">
                <span @class(['text-[15.5px] sm:text-[20px] font-semibold', 'text-muted' => ! $inStock, 'text-espresso' => $inStock])>
                    @if($manyPrices)<span class="text-[12px] sm:text-[13px] font-normal text-muted">শুরু</span>@endif{{ bn_price($price) }}
                </span>
                @if($discount > 0)
                    <span class="text-[12px] sm:text-[15px] text-muted line-through">{{ bn_price($comparePrice) }}</span>
                @endif
            </div>

            @if($product->rating)
                <div class="hidden sm:block mt-2 text-[13.5px] text-muted">★ {{ bn_digits(number_format((float) $product->rating, 1)) }} ({{ bn_digits($product->reviews_count) }}{{ $reviewWord ? ' রিভিউ' : '' }})</div>
            @endif

            {{-- Actions: desktop only; phones tap through to the product page --}}
            <div class="hidden sm:block mt-auto pt-4 relative">
                @if(! $inStock)
                    <button type="button" x-show="! notify" @click="notify = true; $nextTick(() => $refs.contact.focus())"
                            class="w-full bg-sand text-muted rounded-full py-3.5 text-[14.5px] font-semibold hover:bg-line">স্টকে এলে জানান</button>
                    <form x-show="notify" x-cloak method="POST" action="{{ route('store.product.restock', $product->slug) }}"
                          x-data="{ busy: false }"
                          @submit.prevent="
                            busy = true;
                            fetch($el.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData($el) })
                                .then(async (r) => { const d = await r.json(); window.nfFlush?.(d.analytics); $dispatch('cart:toast', { type: r.ok ? 'success' : 'error', msg: d.message }); if (r.ok) { notify = false; $el.reset(); } })
                                .catch(() => $el.submit())
                                .finally(() => busy = false);
                          "
                          class="flex gap-2">
                        @csrf
                        <input x-ref="contact" name="contact" required maxlength="120" placeholder="ইমেইল বা মোবাইল"
                               class="min-w-0 flex-1 bg-sand rounded-full px-4 py-3 text-[14px] text-espresso placeholder-muted outline-none">
                        <button :disabled="busy" class="bg-espresso text-white rounded-full px-4 text-[14px] font-semibold disabled:opacity-60">জানান</button>
                    </form>
                @elseif(! $hasMultiple)
                    <form method="POST" action="{{ route('store.cart.store') }}" class="js-cart-form">
                        @csrf
                        <input type="hidden" name="variant_id" value="{{ $firstInStock?->id }}">
                        <button class="w-full bg-sand-2 text-espresso rounded-full py-3.5 text-[14.5px] font-semibold transition-colors group-hover:bg-espresso group-hover:text-white">ব্যাগে যোগ করুন</button>
                    </form>
                @else
                    <button @click="pick = ! pick" type="button"
                            class="w-full bg-sand-2 text-espresso rounded-full py-3.5 text-[14.5px] font-semibold transition-colors group-hover:bg-espresso group-hover:text-white">
                        <span x-text="pick ? 'বন্ধ করুন' : 'ব্যাগে যোগ করুন'"></span>
                    </button>

                    <div x-show="pick" x-cloak @click.outside="pick = false" x-transition.opacity
                         class="absolute inset-x-0 bottom-[calc(100%-8px)] bg-white rounded-2xl shadow-[0_18px_40px_-16px_rgba(36,28,26,.45)] p-2 max-h-[240px] overflow-y-auto z-10">
                        <form method="POST" action="{{ route('store.cart.store') }}" x-ref="addForm" class="js-cart-form" @cart-added.window="pick = false">
                            @csrf
                            <input type="hidden" name="variant_id" x-ref="vid">
                        </form>
                        <div class="text-[12.5px] text-muted font-medium px-2.5 py-1.5">একটি অপশন বেছে নিন</div>
                        @foreach($variants as $option)
                            <button type="button"
                                    @click="$refs.vid.value = {{ $option->id }}; $refs.addForm.requestSubmit()"
                                    @disabled($option->available_quantity < 1)
                                    class="w-full flex items-center justify-between gap-2 px-2.5 py-2 rounded-xl text-[14px] hover:bg-sand disabled:opacity-40 disabled:cursor-not-allowed">
                                <span class="font-medium text-espresso truncate">{{ bn_digits($option->display_name) }}</span>
                                <span class="flex items-center gap-2 shrink-0">
                                    <span class="font-semibold">{{ bn_price($option->price) }}</span>
                                    @if($option->available_quantity < 1)<span class="text-[11px] text-muted">স্টকে নেই</span>@endif
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
