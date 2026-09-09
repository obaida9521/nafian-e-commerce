<?php

namespace App\Exceptions;

use RuntimeException;

class CouponException extends RuntimeException
{
    public static function notFound(): self
    {
        return new self('This coupon code is invalid.');
    }

    public static function inactive(): self
    {
        return new self('This coupon is no longer active.');
    }

    public static function notStarted(): self
    {
        return new self('This coupon is not valid yet.');
    }

    public static function expired(): self
    {
        return new self('This coupon has expired.');
    }

    public static function usageLimitReached(): self
    {
        return new self('This coupon has reached its usage limit.');
    }

    public static function minimumNotMet(float $min): self
    {
        return new self('Order amount does not meet the minimum of '.number_format($min, 2).' for this coupon.');
    }
}
