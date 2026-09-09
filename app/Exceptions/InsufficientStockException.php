<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public static function forVariant(string $variantName, int $available, int $requested): self
    {
        return new self("Insufficient stock for {$variantName}. Available: {$available}, requested: {$requested}.");
    }
}
