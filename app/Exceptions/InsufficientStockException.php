<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public static function forVariant(string $variantName, int $available, int $requested): self
    {
        return new self("{$variantName}-এর পর্যাপ্ত স্টক নেই। আছে ".bn_digits($available).'টি, চাওয়া হয়েছে '.bn_digits($requested).'টি।');
    }
}
