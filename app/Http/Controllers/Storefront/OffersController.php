<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\CatalogService;
use App\Services\SettingsService;
use Illuminate\View\View;

class OffersController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly SettingsService $settings,
    ) {}

    public function index(): View
    {
        $campaign = $this->settings->group('campaign');

        $coupon = $campaign['coupon_code']
            ? Coupon::query()->active()->where('code', $campaign['coupon_code'])->first()
            : null;

        $endsAt = $coupon?->valid_until && $coupon->valid_until->isFuture() ? $coupon->valid_until : null;

        return view('storefront.offers', [
            'campaign' => $campaign,
            'coupon' => $coupon,
            'endsAt' => $endsAt,
            'combos' => $this->catalog->cardQuery()->where('is_combo', true)->orderByDesc('is_featured')->limit(3)->get(),
            'deals' => $this->catalog->onSale(4),
            'setPicks' => $this->catalog->bestSellers(2),
        ]);
    }
}
