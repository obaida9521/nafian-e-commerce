@php
    /**
     * Rating overview: average, stars, count and the 5→1 breakdown bars.
     *
     * @var \App\Models\Product $product
     * @var \Illuminate\Support\Collection<int, int> $ratingBreakdown  star => count
     * @var int $totalReviews
     */
    $average = (float) ($product->rating ?? 0);
    $maxCount = max(1, $ratingBreakdown->max());
@endphp
<div class="bg-panel rounded-[22px] p-5 sm:p-7 flex sm:flex-col gap-5 sm:gap-0 items-center sm:items-stretch">
    <div class="flex-none text-center sm:text-left">
        <div class="text-[42px] sm:text-[46px] font-semibold text-espresso leading-none">{{ bn_digits(number_format($average, 1)) }}</div>
        <div class="mt-1.5 text-[15px] tracking-[0.08em] leading-none" aria-label="{{ bn_digits(number_format($average, 1)) }} / ৫">
            <span class="text-espresso">{{ str_repeat('★', (int) round($average)) }}</span><span class="text-line">{{ str_repeat('★', 5 - (int) round($average)) }}</span>
        </div>
        <div class="mt-1.5 text-[13px] sm:text-[14.5px] text-muted">{{ bn_digits($totalReviews) }}টি রিভিউ</div>
    </div>

    <div class="flex-1 min-w-0 w-full sm:mt-[18px] flex flex-col gap-1.5 sm:gap-2.5">
        @foreach($ratingBreakdown as $star => $count)
            <div class="flex items-center gap-2.5 sm:gap-3 text-[12.5px] sm:text-[13.5px] text-cocoa">
                <span class="w-4 sm:w-[22px] flex-none">{{ bn_digits($star) }}</span>
                <div class="flex-1 h-1.5 sm:h-2 rounded-full bg-line">
                    <div class="h-full rounded-full bg-espresso" style="width:{{ $totalReviews ? round($count / $maxCount * 100) : 0 }}%"></div>
                </div>
                <span class="w-6 sm:w-[34px] flex-none text-right text-muted">{{ bn_digits($count) }}</span>
            </div>
        @endforeach
    </div>
</div>
