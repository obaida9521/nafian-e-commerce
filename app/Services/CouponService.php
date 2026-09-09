<?php

namespace App\Services;

use App\Exceptions\CouponException;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CouponService
{
    /**
     * Validate a coupon code for a given order amount.
     *
     * @throws CouponException
     */
    public function validate(string $code, float $orderAmount, ?User $user = null): Coupon
    {
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon) {
            throw CouponException::notFound();
        }

        if (! $coupon->is_active) {
            throw CouponException::inactive();
        }

        $now = Carbon::now();

        if ($coupon->valid_from && $now->lt($coupon->valid_from)) {
            throw CouponException::notStarted();
        }

        if ($coupon->valid_until && $now->gt($coupon->valid_until)) {
            throw CouponException::expired();
        }

        if ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
            throw CouponException::usageLimitReached();
        }

        if ($orderAmount < (float) $coupon->min_order_amount) {
            throw CouponException::minimumNotMet((float) $coupon->min_order_amount);
        }

        return $coupon;
    }

    /**
     * Calculate the discount amount for a coupon against a subtotal.
     */
    public function calculateDiscount(Coupon $coupon, float $subtotal): float
    {
        if ($coupon->isPercentage()) {
            $discount = $subtotal * ((float) $coupon->value / 100);
        } else {
            $discount = (float) $coupon->value;
        }

        return round(min($discount, $subtotal), 2);
    }

    /**
     * Record coupon usage and increment its counter atomically.
     */
    public function markUsed(Coupon $coupon, Order $order, ?User $user = null): void
    {
        DB::transaction(function () use ($coupon, $order, $user): void {
            $coupon->increment('used_count');

            $coupon->usages()->firstOrCreate(
                ['order_id' => $order->id],
                [
                    'user_id' => $user?->id,
                    'used_at' => Carbon::now(),
                ],
            );
        });
    }
}
