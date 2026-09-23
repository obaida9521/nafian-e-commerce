<?php

use App\Models\Category;
use App\Services\SettingsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

if (! function_exists('shop_price')) {
    /**
     * Format a money amount using the shop currency symbol (prototype shows "$340").
     */
    function shop_price(float|int|string|null $amount): string
    {
        $symbol = config('shop.currency_symbol', '৳');

        return $symbol.number_format((float) $amount, 0);
    }
}

if (! function_exists('brand_logo')) {
    /**
     * Public URL of the uploaded brand logo, or null when none is set.
     */
    function brand_logo(): ?string
    {
        $path = app(SettingsService::class)->group('general')['logo'] ?? null;

        return $path ? Storage::disk('public')->url($path) : null;
    }
}

if (! function_exists('color_hex')) {
    /**
     * Resolve a colour name to its swatch hex, falling back to a neutral tone.
     */
    function color_hex(?string $name): string
    {
        $colors = config('shop.colors', []);

        return $colors[$name] ?? '#C2BBB0';
    }
}

if (! function_exists('order_status_style')) {
    /**
     * Pill colours for an order status, mirroring the prototype palette.
     *
     * @return array{bg: string, color: string}
     */
    function order_status_style(string $status): array
    {
        $map = [
            'pending' => ['#FEF3C7', '#92400E'],
            'confirmed' => ['#DBEAFE', '#1E40AF'],
            'processing' => ['#E0E7FF', '#3730A3'],
            'shipped' => ['#EDE9FE', '#6D28D9'],
            'delivered' => ['#DCFCE7', '#166534'],
            'cancelled' => ['#FEE2E2', '#991B1B'],
            'refunded' => ['#F3F4F6', '#374151'],
            'paid' => ['#DCFCE7', '#166534'],
            'failed' => ['#FEE2E2', '#991B1B'],
        ];

        $v = $map[strtolower($status)] ?? ['#F3F4F6', '#374151'];

        return ['bg' => $v[0], 'color' => $v[1]];
    }
}

if (! function_exists('estimated_delivery')) {
    /**
     * Human delivery estimate window used on the confirmation screen.
     */
    function estimated_delivery(): string
    {
        $from = Carbon::now()->addDays(3);
        $to = Carbon::now()->addDays(5);

        return $from->format('M j').'–'.$to->format('j, Y');
    }
}

if (! function_exists('bn_digits')) {
    /**
     * Swap Latin digits for Bangla ones (1,450 → ১,৪৫০) for storefront display.
     */
    function bn_digits(string|int|float|null $value): string
    {
        return strtr((string) $value, ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯']);
    }
}

if (! function_exists('bn_price')) {
    /**
     * shop_price() rendered with Bangla digits.
     */
    function bn_price(float|int|string|null $amount): string
    {
        return bn_digits(shop_price($amount));
    }
}

if (! function_exists('latin_digits')) {
    /**
     * Swap Bangla digits for Latin ones (০১৭ → 017) so input can be validated.
     */
    function latin_digits(string $value): string
    {
        return strtr($value, array_flip(['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯']));
    }
}

if (! function_exists('normalize_phone')) {
    /**
     * Canonical Bangladeshi mobile number: 01XXXXXXXXX (country code, spaces and dashes stripped).
     */
    function normalize_phone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', latin_digits($phone)) ?? '';

        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }
}

if (! function_exists('bn_phone')) {
    /**
     * Display a mobile number grouped like the design: ০১৭১২ ৩৪৫ ৬৭৮.
     */
    function bn_phone(?string $phone): string
    {
        $digits = normalize_phone((string) $phone);

        if (strlen($digits) !== 11) {
            return bn_digits((string) $phone);
        }

        return bn_digits(substr($digits, 0, 5).' '.substr($digits, 5, 3).' '.substr($digits, 8));
    }
}

if (! function_exists('bn_date')) {
    /**
     * Bangla date. Tokens: j (day), F (month), M (short month), Y (year), e.g. "২২ সেপ্টেম্বর ২০২৬".
     */
    function bn_date(DateTimeInterface|string|null $date, string $format = 'j F Y'): string
    {
        if ($date === null) {
            return '';
        }

        $date = Carbon::parse($date);
        $months = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];
        $short = ['জানু', 'ফেব্রু', 'মার্চ', 'এপ্রি', 'মে', 'জুন', 'জুলাই', 'আগ', 'সেপ্টে', 'অক্টো', 'নভে', 'ডিসে'];

        $tokens = ['j' => (string) $date->day, 'F' => $months[$date->month - 1], 'M' => $short[$date->month - 1], 'Y' => (string) $date->year];

        return bn_digits(preg_replace_callback('/[jFMY]/', fn (array $m): string => $tokens[$m[0]], $format) ?? '');
    }
}

if (! function_exists('bn_time')) {
    /**
     * Bangla time of day with its period word, e.g. "সকাল ১০:৪২".
     */
    function bn_time(DateTimeInterface|string|null $date): string
    {
        if ($date === null) {
            return '';
        }

        $date = Carbon::parse($date);
        $period = match (true) {
            $date->hour < 5 => 'রাত',
            $date->hour < 12 => 'সকাল',
            $date->hour < 16 => 'দুপুর',
            $date->hour < 18 => 'বিকেল',
            $date->hour < 20 => 'সন্ধ্যা',
            default => 'রাত',
        };

        return $period.' '.bn_digits($date->format('g:i'));
    }
}

if (! function_exists('nav_categories')) {
    /**
     * Top-level categories shown in the storefront nav, footer and mobile chips.
     *
     * @return Collection<int, Category>
     */
    function nav_categories(int $limit = 4): Collection
    {
        return Cache::remember("nav.categories.{$limit}", 300, fn () => Category::query()
            ->active()
            ->parents()
            ->orderBy('sort_order')
            ->limit($limit)
            ->get(['id', 'name', 'slug']));
    }
}
