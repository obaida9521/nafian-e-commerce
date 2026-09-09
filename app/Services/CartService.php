<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    private const SESSION_KEY = 'cart';

    private const COUPON_KEY = 'cart_coupon';

    public function __construct(private readonly CouponService $couponService) {}

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

        $variants = ProductVariant::with('product', 'attributeValues')
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
     * @return array{subtotal: float, discount: float, delivery: float, total: float, coupon: ?string}
     */
    public function getSummary(): array
    {
        $subtotal = $this->getSubtotal();
        $discount = 0.0;
        $code = $this->getCouponCode();

        if ($code !== null) {
            try {
                $coupon = $this->couponService->validate($code, $subtotal, auth()->user());
                $discount = $this->couponService->calculateDiscount($coupon, $subtotal);
            } catch (\Throwable) {
                // Coupon became invalid (expired/maxed) — silently drop it.
                $this->removeCoupon();
                $code = null;
            }
        }

        $freeThreshold = (float) config('shop.free_delivery_threshold', 200);
        $delivery = ($subtotal === 0.0 || $subtotal >= $freeThreshold)
            ? 0.0
            : (float) config('shop.delivery_charge', 12);

        $total = round($subtotal - $discount + $delivery, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => round($discount, 2),
            'delivery' => $delivery,
            'total' => max(0, $total),
            'coupon' => $code,
        ];
    }

    public function getCount(): int
    {
        return (int) collect($this->raw())->sum();
    }

    public function applyCoupon(string $code): void
    {
        // Validates and throws CouponException on failure.
        $this->couponService->validate($code, $this->getSubtotal(), auth()->user());

        Session::put(self::COUPON_KEY, strtoupper($code));
    }

    public function removeCoupon(): void
    {
        Session::forget(self::COUPON_KEY);
    }

    public function getCouponCode(): ?string
    {
        return Session::get(self::COUPON_KEY);
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
