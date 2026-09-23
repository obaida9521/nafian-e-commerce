@props(['rating' => 0])
@php
    /** Five grey stars with yellow ones laid over them, clipped to the exact rating (4.8 → 96% filled). */
    $rating = max(0, min(5, (float) $rating));
@endphp
<span {{ $attributes->merge(['class' => 'relative inline-block leading-none tracking-[0.04em] whitespace-nowrap']) }}
      role="img" aria-label="{{ bn_digits(number_format($rating, 1)) }} / ৫">
    <span class="text-gray-300" aria-hidden="true">★★★★★</span>
    <span class="absolute inset-y-0 left-0 overflow-hidden text-amber-400" style="width: {{ $rating * 20 }}%" aria-hidden="true">★★★★★</span>
</span>
