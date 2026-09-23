<?php

namespace App\Http\Controllers\Storefront;

use App\Exceptions\CouponException;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CheckoutRequest;
use App\Services\AnalyticsService;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly OrderService $orders,
        private readonly SettingsService $settings,
        private readonly AnalyticsService $analytics,
    ) {}

    public function index(): View|RedirectResponse
    {
        $items = $this->cart->getItems();

        if ($items->isEmpty()) {
            return redirect()->route('store.shop')->with('error', 'আপনার ব্যাগ খালি।');
        }

        $summary = $this->cart->getSummary();
        $this->analytics->beginCheckout($items, $summary);

        $user = auth('web')->user();
        $address = $user?->addresses()->orderByDesc('is_default')->first();

        return view('storefront.checkout', [
            'items' => $items,
            'summary' => $summary,
            'general' => $this->settings->group('general'),
            'prefill' => [
                'name' => $address->recipient_name ?? $user?->name,
                'phone' => $address->phone ?? $user?->phone,
                'email' => $user?->email,
                'address' => $address->address_line1 ?? null,
                'city' => in_array($address?->city, config('shop.cities'), true) ? $address->city : config('shop.inside_city'),
                'area' => $address->address_line2 ?? null,
                'postcode' => $address->postal_code ?? null,
            ],
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $items = $this->cart->getItems();

        if ($items->isEmpty()) {
            return redirect()->route('store.shop')->with('error', 'আপনার ব্যাগ খালি।');
        }

        $data = $request->validated();
        $zone = $request->deliveryZone();
        $this->cart->setDeliveryZone($zone);
        $summary = $this->cart->getSummary($zone);

        $orderData = [
            'items' => $items->map(fn ($line) => [
                'variant_id' => $line['variant']->id,
                'quantity' => $line['quantity'],
            ])->all(),
            'shipping_name' => $data['name'],
            'shipping_phone' => $data['phone'],
            'shipping_address' => $data['address'],
            'shipping_city' => $data['city'],
            'shipping_area' => $data['area'],
            'shipping_district' => $data['city'],
            'shipping_postcode' => $data['postcode'] ?: null,
            'delivery_zone' => $zone,
            'payment_method' => $data['payment_method'],
            'coupon_code' => $summary['coupon'],
            'delivery_charge' => $summary['delivery'],
            'notes' => $data['notes'] ?? null,
            'guest_email' => $data['email'] ?? auth('web')->user()?->email,
            'guest_phone' => $data['phone'],
        ];

        try {
            $order = $this->orders->createOrder($orderData, auth('web')->user());
        } catch (InsufficientStockException|CouponException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->cart->clear();
        $this->analytics->purchase($order);

        // Remember the order for this session so the confirmation and tracking pages open it.
        session()->push('recent_orders', $order->order_number);

        return redirect()
            ->route('store.order.confirmation', $order->order_number)
            ->with('placed_order', $order->order_number);
    }
}
