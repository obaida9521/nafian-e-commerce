@php
    /** @var \App\Models\Product $product */
    $cat = $product->categories->first()?->name ?? ($product->relationLoaded('categories') ? '' : '');
    $minPrice = $product->variants->min('price');
    $colorCount = count($product->colorOptions());
    $inStock = $product->total_stock > 0;
    $image = $product->getFirstMediaUrl('images', 'thumb') ?: $product->getFirstMediaUrl('images');

    $variants = $product->variants->where('is_active', true)->values();
    $hasMultiple = $variants->count() > 1;
    $firstInStock = $variants->firstWhere(fn ($v) => $v->available_quantity > 0);
    $variantPayload = $variants->map(fn ($v) => [
        'id' => $v->id,
        'name' => $v->display_name,
        'price' => (float) $v->price,
        'available' => $v->available_quantity,
    ])->values();
@endphp
<div x-data="{ pick: false }">
    <div class="relative rounded-[11px] overflow-hidden aspect-[4/5]" style="background:linear-gradient(155deg,{{ $product->tone ?? '#C2BBB0' }},{{ $product->tone2 ?? '#A39B8E' }});">
        @if($image)
            <img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
        @endif
        <a href="{{ route('store.product', $product->slug) }}" class="absolute inset-0"></a>
        @if($product->badge && $product->badge !== 'Best Seller')
            <span class="absolute top-[11px] left-[11px] bg-[#FAFAF8]/90 text-[11px] font-semibold px-[9px] py-1 rounded-[20px] text-[#691d2a]">{{ $product->badge }}</span>
        @endif

        @if(! $inStock)
            <div class="absolute left-[11px] right-[11px] bottom-[11px] h-[38px] rounded-[7px] bg-white/80 text-gray-500 text-[13px] font-semibold flex items-center justify-center">Sold out</div>
        @elseif(! $hasMultiple)
            {{-- Single variant: add straight to the bag. --}}
            <form method="POST" action="{{ route('store.cart.store') }}" class="js-cart-form absolute left-[11px] right-[11px] bottom-[11px]">
                @csrf
                <input type="hidden" name="variant_id" value="{{ $firstInStock?->id }}">
                <button class="w-full h-[38px] rounded-[7px] text-white text-[13px] font-semibold" style="background:rgba(105,29,42,0.92);backdrop-filter:blur(4px);">Quick add</button>
            </form>
        @else
            {{-- Multiple variants: pick one before adding. --}}
            <div class="absolute left-[11px] right-[11px] bottom-[11px]">
                <button @click="pick = !pick" type="button"
                    class="w-full h-[38px] rounded-[7px] text-white text-[13px] font-semibold" style="background:rgba(105,29,42,0.92);backdrop-filter:blur(4px);">
                    <span x-text="pick ? 'Close' : 'Choose options'"></span>
                </button>
            </div>

            {{-- Variant picker --}}
            <div x-show="pick" x-cloak @click.outside="pick = false" x-transition.opacity
                class="absolute inset-x-[11px] bottom-[11px] bg-white rounded-[9px] shadow-xl border border-[#EADBC4] p-2 max-h-[72%] overflow-y-auto scrollbar-thin">
                <form method="POST" action="{{ route('store.cart.store') }}" x-ref="addForm" class="js-cart-form" @cart-added.window="pick = false">
                    @csrf
                    <input type="hidden" name="variant_id" x-ref="vid">
                </form>
                <div class="text-[11px] uppercase tracking-wide text-gray-400 font-semibold px-1.5 py-1">Select variant</div>
                @foreach($variantPayload as $v)
                    <button type="button"
                        @click="$refs.vid.value = {{ $v['id'] }}; $refs.addForm.requestSubmit()"
                        @if($v['available'] < 1) disabled @endif
                        class="w-full flex items-center justify-between gap-2 px-2 py-2 rounded-[6px] text-[13px] hover:bg-[#fff2e3] disabled:opacity-40 disabled:cursor-not-allowed">
                        <span class="font-medium text-gray-800 truncate">{{ $v['name'] }}</span>
                        <span class="flex items-center gap-2 shrink-0">
                            <span class="font-semibold">{{ shop_price($v['price']) }}</span>
                            @if($v['available'] < 1)
                                <span class="text-[10px] text-red-500">Sold out</span>
                            @endif
                        </span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
    <a href="{{ route('store.product', $product->slug) }}" class="block pt-3">
        <div class="text-[11px] tracking-[0.08em] uppercase text-gray-400 font-semibold">{{ $product->categories->first()?->name }}</div>
        <div class="font-semibold text-[15px] mt-[3px] mb-0.5">{{ $product->name }}</div>
        <div class="flex items-center justify-between">
            <span class="text-[15px] font-semibold">{{ shop_price($minPrice) }}</span>
            <span class="text-xs text-gray-400">{{ $colorCount }} {{ Str::plural('colour', $colorCount) }}</span>
        </div>
    </a>
</div>
