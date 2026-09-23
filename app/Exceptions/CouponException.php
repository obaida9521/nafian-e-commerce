<?php

namespace App\Exceptions;

use RuntimeException;

class CouponException extends RuntimeException
{
    public static function notFound(): self
    {
        return new self('কুপন কোডটি সঠিক নয়।');
    }

    public static function inactive(): self
    {
        return new self('এই কুপনটি এখন আর চালু নেই।');
    }

    public static function notStarted(): self
    {
        return new self('এই কুপনটি এখনো চালু হয়নি।');
    }

    public static function expired(): self
    {
        return new self('এই কুপনের মেয়াদ শেষ।');
    }

    public static function usageLimitReached(): self
    {
        return new self('এই কুপনের ব্যবহারের সীমা শেষ।');
    }

    public static function needsTwoItems(): self
    {
        return new self('এই কুপনের জন্য দুটি বা তার বেশি বোতল লাগবে।');
    }

    public static function minimumNotMet(float $min): self
    {
        return new self('এই কুপনের জন্য ন্যূনতম ৳'.number_format($min).' অর্ডার করতে হবে।');
    }
}
