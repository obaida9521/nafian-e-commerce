<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    private const SESSION_KEY = 'cart';

    private const COUPON_KEY = 'cart_coupon';

    private const ZONE_KEY = 'cart_delivery_zone';

    /**
     * Delivery zones a shopper can pick (inside / outside Dhaka).
     *
     * @var list<string>
     */
    public const ZONES = ['inside', 'outside'];

    public function __construct(
        private readonly CouponService $couponService,
        private readonly SettingsService $settings,
    ) {}

    public function add(int $variantId, int $quantity = 1): void
    {
        $cart = $this->raw();
        $cart[$variantId] = ($cart[$variantId] ?? 0) + max(1, $quantity);
        $this->persist($cart);
    }

    public function update(int $variantId, int $quantity): void
    {
        $cart = $this->raw();

        if ($quantity <= 0) {
            unset($cart[$variantId]);
        } else {
            $cart[$variantId] = $quantity;
        }

        $this->persist($cart);
    }

    public function remove(int $variantId): void
    {
        $cart = $this->raw();
        unset($cart[$variantId]);
        $this->persist($cart);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::forget(self::COUPON_KEY);
    }

    /**
     * Cart lines hydrated with their variant + product.
     *
     * @return Collection<int, array{variant: ProductVariant, quantity: int, line_total: float}>
     */
    public function getItems(): Collection
    {
        $cart = $this->raw();

        if ($cart === []) {
            return collect();
        }

        $variants = ProductVariant::with('product.categories', 'product.media', 'media', 'attributeValues.attribute')
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        return collect($cart)
            ->filter(fn ($qty, $id) => $variants->has($id))
            ->map(fn (int $qty, int $id) => [
                'variant' => $variants[$id],
                'quantity' => $qty,
                'line_total' => round((float) $variants[$id]->price * $qty, 2),
            ])
            ->values();
    }

    public function getSubtotal(): float
    {
        return round($this->getItems()->sum('line_total'), 2);
    }

    /**
     * Pricing breakdown for the cart drawer and checkout summary.
     *
     * @return array{subtotal: float, discount: float, delivery: float, total: float, coupon: ?string, zone: string, free_delivery_threshold: float}
     */
    public function getSummary(?string $zone = null): array
    {
        $subtotal = $this->getSubtotal();
        $discount = 0.0;
        $code = $this->getCouponCode();

        if ($code !== null) {
            try {
                $coupon = $this->couponService->validate($code, $subtotal, auth()->user(), $this->getCount());
                $discount = $this->couponService->calculateDiscount($coupon, $subtotal, $this->unitPrices());
            } catch (\Throwable) {
                // Coupon became invalid (expired/maxed) — silently drop it.
                $this->removeCoupon();
                $code = null;
            }
        }

        $zone = in_array($zone, self::ZONES, true) ? $zone : $this->getDeliveryZone();
        $delivery = $this->deliveryCharge($zone, $subtotal);
        $freeThreshold = (float) $this->settings->group('general')['free_delivery_threshold'];

        $total = round($subtotal - $discount + $delivery, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => round($discount, 2),
            'delivery' => $delivery,
            'total' => max(0, $total),
            'coupon' => $code,
            'zone' => $zone,
            'free_delivery_threshold' => $freeThreshold,
        ];
    }

    public function getCount(): int
    {
        return (int) collect($this->raw())->sum();
    }

    public function applyCoupon(string $code): void
    {
        // Validates and throws CouponException on failure.
        $this->couponService->validate(strtoupper($code), $this->getSubtotal(), auth()->user(), $this->getCount());

        Session::put(self::COUPON_KEY, strtoupper($code));
    }

    public function removeCoupon(): void
    {
        Session::forget(self::COUPON_KEY);
    }

    /**
     * Delivery charge for a zone; free once the subtotal reaches the threshold.
     */
    public function deliveryCharge(string $zone, float $subtotal): float
    {
        $general = $this->settings->group('general');
        $threshold = (float) $general['free_delivery_threshold'];

        if ($subtotal <= 0 || ($threshold > 0 && $subtotal >= $threshold)) {
            return 0.0;
        }

        return (float) ($zone === 'outside' ? $general['delivery_outside'] : $general['delivery_inside']);
    }

    public function setDeliveryZone(string $zone): void
    {
        if (in_array($zone, self::ZONES, true)) {
            Session::put(self::ZONE_KEY, $zone);
        }
    }

    public function getDeliveryZone(): string
    {
        return Session::get(self::ZONE_KEY, 'inside');
    }

    public function getCouponCode(): ?string
    {
        return Session::get(self::COUPON_KEY);
    }

    /**
     * One price per unit in the bag, for per-item coupon maths.
     *
     * @return list<float>
     */
    private function unitPrices(): array
    {
        return $this->getItems()
            ->flatMap(fn (array $line) => array_fill(0, $line['quantity'], (float) $line['variant']->price))
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function raw(): array
    {
        /** @var array<int, int> $cart */
        $cart = Session::get(self::SESSION_KEY, []);

        return $cart;
    }

    /**
     * @param  array<int, int>  $cart
     */
    private function persist(array $cart): void
    {
        Session::put(self::SESSION_KEY, $cart);
    }
}
