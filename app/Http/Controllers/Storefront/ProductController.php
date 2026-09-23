<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\AnalyticsService;
use App\Services\CatalogService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProductController extends Controller
{
    /** Reviews previewed on the product page before "see all". */
    private const PREVIEW_REVIEWS = 3;

    private const REVIEWS_PER_PAGE = 10;

    public function __construct(
        private readonly CatalogService $catalog,
        private readonly AnalyticsService $analytics,
    ) {}

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load('variants.attributeValues.attribute', 'variants.media', 'categories', 'reviews.user', 'media');

        $category = $product->categories->first();

        $related = $this->catalog->cardQuery()
            ->where('products.id', '!=', $product->id)
            ->when($category, fn ($q) => $q->whereHas('categories', fn ($c) => $c->where('categories.id', $category->id)))
            ->limit(4)
            ->get();

        if ($related->count() < 4) {
            $related = $related->merge(
                $this->catalog->cardQuery()
                    ->where('products.id', '!=', $product->id)
                    ->whereNotIn('products.id', $related->pluck('id'))
                    ->orderByDesc('reviews_count')
                    ->limit(4 - $related->count())
                    ->get()
            );
        }

        $previewReviews = $product->reviews->take(self::PREVIEW_REVIEWS);

        $this->analytics->viewItem($product);

        $userReview = auth('web')->check()
            ? $product->reviews->firstWhere('user_id', auth('web')->id())
            : null;

        return view('storefront.pdp', [
            'product' => $product,
            'related' => $related,
            'colorOptions' => $product->colorOptions(),
            'sizeOptions' => $product->sizeOptions(),
            'userReview' => $userReview,
            'ratingBreakdown' => $this->ratingBreakdown($product),
            'previewReviews' => $previewReviews,
            'verifiedBuyers' => $this->verifiedBuyers($product, $previewReviews->pluck('user_id')->all()),
            'general' => app(SettingsService::class)->group('general'),
        ]);
    }

    /**
     * All reviews for a product, newest first, optionally narrowed to one star rating.
     */
    public function reviews(Request $request, Product $product): View
    {
        abort_unless($product->is_active, 404);

        $star = $request->integer('rating');
        $star = $star >= 1 && $star <= 5 ? $star : null;

        $reviews = $product->reviews()
            ->with('user')
            ->when($star, fn ($q) => $q->where('rating', $star))
            ->paginate(self::REVIEWS_PER_PAGE)
            ->withQueryString();

        $userReview = auth('web')->check()
            ? $product->reviews()->where('user_id', auth('web')->id())->first()
            : null;

        return view('storefront.reviews', [
            'product' => $product,
            'reviews' => $reviews,
            'star' => $star,
            'totalReviews' => $product->reviews()->count(),
            'ratingBreakdown' => $this->ratingBreakdown($product),
            'verifiedBuyers' => $this->verifiedBuyers($product, $reviews->pluck('user_id')->all()),
            'userReview' => $userReview,
        ]);
    }

    public function storeReview(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:150'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $product->reviews()->updateOrCreate(
            ['user_id' => auth('web')->id()],
            [
                'rating' => $validated['rating'],
                'title' => $validated['title'] ?? null,
                'body' => $validated['body'] ?? null,
            ],
        );

        $product->recalculateRating();

        $message = 'ধন্যবাদ! আপনার রিভিউ প্রকাশিত হয়েছে।';

        if ($request->input('from') === 'reviews') {
            return redirect()->route('store.product.reviews', $product->slug)->with('success', $message);
        }

        return redirect()
            ->route('store.product', $product->slug)
            ->with('success', $message)
            ->withFragment('reviews');
    }

    /**
     * Review count per star, 5 → 1.
     *
     * @return Collection<int, int>
     */
    private function ratingBreakdown(Product $product): Collection
    {
        $counts = $product->reviews()->reorder()->selectRaw('rating, count(*) as total')->groupBy('rating')->pluck('total', 'rating');

        return collect([5, 4, 3, 2, 1])->mapWithKeys(fn (int $star) => [$star => (int) ($counts[$star] ?? 0)]);
    }

    /**
     * Which of these reviewers received this product (delivered order), keyed by user id => variant bought.
     *
     * @param  list<int>  $userIds
     * @return Collection<int, string>
     */
    private function verifiedBuyers(Product $product, array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        return OrderItem::query()
            ->whereHas('variant', fn ($q) => $q->withTrashed()->where('product_id', $product->id))
            ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Delivered)->whereIn('user_id', $userIds))
            ->with('order:id,user_id')
            ->get()
            ->mapWithKeys(fn (OrderItem $item) => [$item->order->user_id => $item->variant_name]);
    }
}
