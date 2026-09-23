@php
    /**
     * @var \App\Models\Product $product
     * @var \App\Models\ProductReview|null $userReview
     * @var string|null $from  'reviews' sends the shopper back to the reviews page after posting
     */
@endphp
@auth('web')
    <form id="write-review" method="POST" action="{{ route('store.product.review', $product->slug) }}" class="bg-panel rounded-[20px] p-5 sm:p-6 scroll-mt-24">
        @csrf
        @if($from ?? null)<input type="hidden" name="from" value="{{ $from }}">@endif
        <div class="text-[16px] font-semibold">{{ $userReview ? 'আপনার রিভিউ সম্পাদনা করুন' : 'রিভিউ লিখুন' }}</div>
        <div class="mt-3.5 flex gap-1.5 items-center" x-data="{ rating: {{ old('rating', $userReview->rating ?? 5) }} }">
            <template x-for="star in 5" :key="star">
                <button type="button" @click="rating = star" class="text-[26px] leading-none" :class="star <= rating ? 'text-espresso' : 'text-line'" :aria-label="bnNumber(star) + ' স্টার'">★</button>
            </template>
            <input type="hidden" name="rating" :value="rating">
        </div>
        <textarea name="body" rows="3" maxlength="2000" placeholder="সুগন্ধি নিয়ে আপনার অভিজ্ঞতা লিখুন" class="nf-input mt-3.5">{{ old('body', $userReview->body ?? '') }}</textarea>
        <button class="mt-3.5 w-full sm:w-auto bg-espresso text-white rounded-full px-7 py-3.5 text-[14.5px] font-semibold hover:bg-ink">জমা দিন</button>
    </form>
@else
    <a href="{{ route('login') }}" class="block text-center bg-panel rounded-[20px] p-5 text-[14.5px] font-semibold text-espresso">রিভিউ লিখতে লগইন করুন →</a>
@endauth
