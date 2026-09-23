@php
    /**
     * One customer review.
     *
     * @var \App\Models\ProductReview $review
     * @var string|null $verifiedLabel  variant the reviewer bought (delivered order), if any
     * @var bool $clamp  shorten long reviews (previews on the product page)
     */
    $clamp = $clamp ?? false;
    $name = $review->user?->name ?? 'ক্রেতা';
@endphp
<article class="bg-panel rounded-[20px] p-[18px] sm:p-6">
    <div class="flex items-center gap-3">
        <span class="flex-none w-9 h-9 rounded-full bg-sand-2 text-espresso grid place-items-center text-[14px] font-semibold" aria-hidden="true">{{ mb_substr($name, 0, 1) }}</span>
        <div class="min-w-0 flex-1">
            <div class="flex items-baseline justify-between gap-2">
                <div class="text-[15px] sm:text-[15.5px] font-semibold truncate">{{ $name }}</div>
                <div class="flex-none text-[12.5px] sm:text-[13.5px] text-muted">{{ bn_date($review->created_at) }}</div>
            </div>
            <div class="mt-0.5 flex items-center gap-2 text-[12.5px] sm:text-[13px]">
                <span class="tracking-[0.06em]" aria-label="{{ bn_digits($review->rating) }} / ৫">
                    <span class="text-espresso">{{ str_repeat('★', $review->rating) }}</span><span class="text-line">{{ str_repeat('★', 5 - $review->rating) }}</span>
                </span>
                @if($verifiedLabel ?? null)
                    <span class="text-accent truncate">✓ ভেরিফাইড ক্রেতা · {{ bn_digits($verifiedLabel) }}</span>
                @endif
            </div>
        </div>
    </div>
    @if($review->title)
        <div class="mt-3 text-[15px] font-semibold">{{ $review->title }}</div>
    @endif
    @if($review->body)
        <p @class(['text-[14.5px] sm:text-[16px] leading-[1.8] text-bark', 'mt-1.5' => $review->title, 'mt-3' => ! $review->title, 'line-clamp-4' => $clamp])>{{ $review->body }}</p>
    @endif
</article>
