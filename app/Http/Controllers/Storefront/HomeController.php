<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Services\CatalogService;
use App\Services\SettingsService;
use Illuminate\View\View;

class HomeController extends Controller
{
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

        return view('storefront.home', [
            'bestSellers' => $this->catalog->bestSellers(4),
            'collections' => $collections,
            'reviews' => ProductReview::query()
                ->where('rating', '>=', 4)
                ->whereNotNull('body')
                ->with(['user', 'product'])
                ->latest()
                ->limit(3)
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
}
