@props(['product', 'variant' => null, 'conversion' => 'thumb', 'label' => null, 'hover' => false])
@php
    /** @var \App\Models\Product|null $product */
    /** @var \App\Models\ProductVariant|null $variant  its own photo wins over the product's */
    $tone = $product?->tone ?? '#C2BBB0';
    $urlOf = fn ($media) => $media ? ($media->getUrl($conversion) ?: $media->getUrl()) : null;

    $src = $variant ? $urlOf($variant->getFirstMedia('image')) : null;
    $src = $src ?: ($product ? $urlOf($product->getFirstMedia('images')) : null);

    // Hover swap (product cards): the product's second photo, else the first variant photo.
    $hoverSrc = null;
    if ($hover && $product && $src) {
        $hoverSrc = $urlOf($product->getMedia('images')->get(1));
        if (! $hoverSrc) {
            $variantWithImage = $product->variants->first(fn ($v) => $v->is_active && $v->getFirstMedia('image'));
            $hoverSrc = $urlOf($variantWithImage?->getFirstMedia('image'));
        }
        $hoverSrc = $hoverSrc !== $src ? $hoverSrc : null;
    }
@endphp
{{-- Product photo over a soft tone gradient (the gradient shows until/unless a photo loads). --}}
<div {{ $attributes->merge(['class' => 'relative overflow-hidden'.($hoverSrc ? ' group/img' : '')]) }}
     style="background:linear-gradient(160deg, color-mix(in srgb, {{ $tone }} 12%, #F6F3F1), color-mix(in srgb, {{ $tone }} 30%, #ECE7E3));">
    @if($src)
        <img src="{{ $src }}" alt="{{ $product->name }}" loading="lazy" onerror="this.remove()" class="absolute inset-0 w-full h-full object-cover">
    @endif
    @if($hoverSrc)
        {{-- Fades in over the first photo while the image is hovered, and back out on leave. --}}
        <img src="{{ $hoverSrc }}" alt="" aria-hidden="true" loading="lazy" onerror="this.remove()"
             class="absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-[800ms] ease-in-out group-hover/img:opacity-100 motion-reduce:transition-none">
    @endif
    {{ $slot }}
</div>
