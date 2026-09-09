<?php

use Illuminate\Support\Carbon;

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
        $path = app(\App\Services\SettingsService::class)->group('general')['logo'] ?? null;

        return $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
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
