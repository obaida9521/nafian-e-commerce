<?php

return [
    'name' => env('SHOP_NAME', 'Nafian'),
    'currency' => env('SHOP_CURRENCY', 'BDT'),
    'currency_symbol' => env('SHOP_CURRENCY_SYMBOL', '৳'),
    'low_stock_threshold' => env('LOW_STOCK_THRESHOLD', 5),
    'free_delivery_threshold' => env('FREE_DELIVERY_THRESHOLD', 200),
    'delivery_charge' => env('DELIVERY_CHARGE', 12),
    'reservation_ttl_minutes' => [
        'online' => 15,
        'cod' => 1440, // 24 hours
    ],
    'order_number_prefix' => 'NF',
    'per_page' => 20,

    /*
    |--------------------------------------------------------------------------
    | Colour swatch palette (name => hex) — mirrors the prototype catalog.
    | Used to render colour dots/swatches across PLP, PDP and the cart.
    |--------------------------------------------------------------------------
    */
    'colors' => [
        'Tan' => '#C9B49A',
        'Black' => '#2A2A2A',
        'Cognac' => '#8B5A2B',
        'Camel' => '#C19A6B',
        'Charcoal' => '#3A3A3A',
        'Oat' => '#D8CBB6',
        'Slate' => '#6B7280',
        'Burgundy' => '#6E2B3A',
        'Ivory' => '#EDE8DD',
        'Sage' => '#A3B18A',
        'Steel' => '#B8BEC4',
        'Gold' => '#C8A84B',
        'Stone' => '#B7AE9F',
        'Navy' => '#2B3A55',
        'Heather' => '#B6B0A6',
        'Forest' => '#2F4A3A',
        'Tortoise' => '#7A4A28',
    ],
];
