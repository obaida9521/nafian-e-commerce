@php
    /** @var array{variant: \App\Models\ProductVariant, quantity: int, line_total: float} $line */
    $variant = $line['variant'];
    $product = $variant->product;
    $compact = $compact ?? false;
    $bagPage = $bagPage ?? false;
    $meta = collect([$product->categories->first()?->name, bn_digits($variant->shortLabel())])->filter()->implode(' · ');
    $hasCompare = (float) $variant->compare_at_price > (float) $variant->price;
@endphp

@if($compact)
    {{-- Phone layout (Nafian Mobile · ব্যাগ) --}}
    <div class="bg-panel rounded-[18px] p-3.5 flex gap-[13px]">
        <a href="{{ route('store.product', $product->slug) }}" class="flex-none">
            <x-ui.product-image :product="$product" :variant="$variant" class="w-[76px] h-[88px] rounded-[13px]" />
        </a>
        <div class="flex-1 min-w-0">
            <div class="flex justify-between gap-2">
                <a href="{{ route('store.product', $product->slug) }}" class="text-[15.5px] font-semibold truncate">{{ $product->name }}</a>
                <form method="POST" action="{{ route('store.cart.destroy', $variant->id) }}" class="js-cart-form flex-none">
                    @csrf @method('DELETE')
                    @if($bagPage)<input type="hidden" name="bag_page" value="1">@endif
                    <button class="text-[12.5px] text-muted hover:text-rose">সরান</button>
                </form>
            </div>
            <div class="mt-0.5 text-[12.5px] text-muted truncate">{{ $meta }}</div>
            <div class="mt-2 flex items-center justify-between">
                @include('storefront.partials.bag-stepper', ['size' => 'sm'])
                <div class="text-[16px] font-semibold text-espresso">{{ bn_price($line['line_total']) }}</div>
            </div>
        </div>
    </div>
@else
    {{-- Desktop layout (Nafian Checkout · আপনার ব্যাগ) --}}
    <div class="bg-panel rounded-[20px] p-[18px] flex gap-[18px] items-center flex-wrap">
        <a href="{{ route('store.product', $product->slug) }}" class="flex-none">
            <x-ui.product-image :product="$product" :variant="$variant" class="w-24 h-[106px] rounded-[14px]" />
        </a>
        <div class="flex-1 min-w-[180px]">
            <a href="{{ route('store.product', $product->slug) }}" class="text-[18px] font-semibold hover:text-accent">{{ $product->name }}</a>
            <div class="mt-[3px] text-[14px] text-muted">{{ $meta }}</div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-[17px] font-semibold text-espresso">{{ bn_price($variant->price) }}</span>
                @if($hasCompare)<span class="text-[14px] text-muted line-through">{{ bn_price($variant->compare_at_price) }}</span>@endif
            </div>
        </div>
        @include('storefront.partials.bag-stepper', ['size' => 'lg'])
        <div class="text-right min-w-[100px]">
            <div class="text-[17px] font-semibold">{{ bn_price($line['line_total']) }}</div>
            <form method="POST" action="{{ route('store.cart.destroy', $variant->id) }}" class="js-cart-form mt-1.5">
                @csrf @method('DELETE')
                @if($bagPage)<input type="hidden" name="bag_page" value="1">@endif
                <button class="text-[13.5px] text-muted hover:text-rose">সরান</button>
            </form>
        </div>
    </div>
@endif
