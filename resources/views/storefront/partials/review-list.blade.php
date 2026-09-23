@php
    /**
     * @var \Illuminate\Support\Collection<int, \App\Models\ProductReview> $reviews
     * @var \Illuminate\Support\Collection<int, string> $verifiedBuyers  user_id => variant name
     */
    $clamp = $clamp ?? false;
@endphp
@if($reviews->isEmpty())
    <div class="bg-panel rounded-[20px] p-6 text-[14.5px] text-muted">{{ $empty ?? 'এখনো কোনো রিভিউ আসেনি। প্রথম রিভিউটি আপনিই লিখুন।' }}</div>
@else
    <div class="flex flex-col gap-3 sm:gap-4">
        @foreach($reviews as $review)
            @include('storefront.partials.review-card', ['review' => $review, 'verifiedLabel' => $verifiedBuyers[$review->user_id] ?? null, 'clamp' => $clamp])
        @endforeach
    </div>
@endif
