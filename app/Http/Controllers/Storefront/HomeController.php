<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Services\CatalogService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** Products shown in "দেখতে থাকুন" on page load, and how many each "আরও দেখুন" click adds. */
    private const DISCOVER_FIRST_PAGE = 4;

    private const DISCOVER_PAGE = 4;

    public function __construct(
        private readonly CatalogService $catalog,
        private readonly SettingsService $settings,
    ) {}

    public function index(): View
    {
        $collections = Category::query()
            ->active()
            ->parents()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderByDesc('show_on_home')
            ->orderBy('sort_order')
            ->limit(3)
            ->get()
            ->each(fn (Category $category) => $category->setAttribute('min_price', ProductVariant::query()
                ->active()
                ->whereHas('product', fn ($q) => $q->active()->whereHas('categories', fn ($c) => $c->where('categories.id', $category->id)))
                ->min('price')));

        $campaign = $this->settings->group('campaign');
        $campaignCoupon = $campaign['enabled'] && $campaign['coupon_code']
            ? Coupon::query()->active()->where('code', $campaign['coupon_code'])->first()
            : null;

        $discoverSeed = random_int(1, 999999);

        return view('storefront.home', [
            'bestSellers' => $this->catalog->bestSellers(4),
            'discoverSeed' => $discoverSeed,
            'discover' => $this->catalog->discover($discoverSeed, 0, self::DISCOVER_FIRST_PAGE),
            'collections' => $collections,
            'reviews' => ProductReview::query()
                ->where('rating', '>=', 4)
                ->whereNotNull('body')
                ->with(['user', 'product'])
                ->latest()
                ->limit(10)
                ->get(),
            'reviewStats' => [
                'average' => round((float) ProductReview::avg('rating'), 1),
                'count' => ProductReview::count(),
            ],
            'campaign' => $campaign,
            'campaignCoupon' => $campaignCoupon,
            'general' => $this->settings->group('general'),
        ]);
    }

    /**
     * Next page of "দেখতে থাকুন" cards for the home page's "আরও দেখুন" button.
     */
    public function discover(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'seed' => ['required', 'integer', 'min:1'],
            'offset' => ['required', 'integer', 'min:0', 'max:500'],
        ]);

        $page = $this->catalog->discover((int) $validated['seed'], (int) $validated['offset'], self::DISCOVER_PAGE);

        return response()->json([
            'html' => view('storefront.partials.discover-items', ['products' => $page['products']])->render(),
            'count' => $page['products']->count(),
            'has_more' => $page['has_more'],
        ]);
    }
}
