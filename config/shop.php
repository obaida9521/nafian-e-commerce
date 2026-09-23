<?php

return [
    'name' => env('SHOP_NAME', 'Nafian'),
    'currency' => env('SHOP_CURRENCY', 'BDT'),
    'currency_symbol' => env('SHOP_CURRENCY_SYMBOL', '৳'),
    'low_stock_threshold' => env('LOW_STOCK_THRESHOLD', 10),
    'reservation_ttl_minutes' => [
        'online' => 15,
        'cod' => 1440, // 24 hours
    ],
    'order_number_prefix' => 'NFN',
    'per_page' => 12,

    /*
    |--------------------------------------------------------------------------
    | Checkout geography — the inside-Dhaka rate applies to `inside_city`.
    |--------------------------------------------------------------------------
    */
    'inside_city' => 'ঢাকা',

    /*
    | All 64 districts, grouped by division: Bangla name => English name (the English
    | name is extra search text in the checkout picker). Read the flat list via bd_districts().
    */
    'districts' => [
        'ঢাকা বিভাগ' => [
            'ঢাকা' => 'Dhaka', 'গাজীপুর' => 'Gazipur', 'নারায়ণগঞ্জ' => 'Narayanganj', 'নরসিংদী' => 'Narsingdi',
            'মানিকগঞ্জ' => 'Manikganj', 'মুন্সিগঞ্জ' => 'Munshiganj', 'টাঙ্গাইল' => 'Tangail', 'কিশোরগঞ্জ' => 'Kishoreganj',
            'ফরিদপুর' => 'Faridpur', 'গোপালগঞ্জ' => 'Gopalganj', 'মাদারীপুর' => 'Madaripur', 'শরীয়তপুর' => 'Shariatpur',
            'রাজবাড়ী' => 'Rajbari',
        ],
        'চট্টগ্রাম বিভাগ' => [
            'চট্টগ্রাম' => 'Chattogram Chittagong', 'কক্সবাজার' => "Cox's Bazar Coxs Bazar", 'কুমিল্লা' => 'Cumilla Comilla',
            'ফেনী' => 'Feni', 'নোয়াখালী' => 'Noakhali', 'লক্ষ্মীপুর' => 'Lakshmipur', 'চাঁদপুর' => 'Chandpur',
            'ব্রাহ্মণবাড়িয়া' => 'Brahmanbaria', 'রাঙ্গামাটি' => 'Rangamati', 'খাগড়াছড়ি' => 'Khagrachhari', 'বান্দরবান' => 'Bandarban',
        ],
        'রাজশাহী বিভাগ' => [
            'রাজশাহী' => 'Rajshahi', 'নাটোর' => 'Natore', 'নওগাঁ' => 'Naogaon', 'চাঁপাইনবাবগঞ্জ' => 'Chapainawabganj Chapai Nawabganj',
            'পাবনা' => 'Pabna', 'সিরাজগঞ্জ' => 'Sirajganj', 'বগুড়া' => 'Bogura Bogra', 'জয়পুরহাট' => 'Joypurhat',
        ],
        'খুলনা বিভাগ' => [
            'খুলনা' => 'Khulna', 'যশোর' => 'Jashore Jessore', 'সাতক্ষীরা' => 'Satkhira', 'বাগেরহাট' => 'Bagerhat',
            'নড়াইল' => 'Narail', 'মাগুরা' => 'Magura', 'ঝিনাইদহ' => 'Jhenaidah', 'কুষ্টিয়া' => 'Kushtia',
            'চুয়াডাঙ্গা' => 'Chuadanga', 'মেহেরপুর' => 'Meherpur',
        ],
        'বরিশাল বিভাগ' => [
            'বরিশাল' => 'Barishal Barisal', 'পটুয়াখালী' => 'Patuakhali', 'ভোলা' => 'Bhola', 'পিরোজপুর' => 'Pirojpur',
            'বরগুনা' => 'Barguna', 'ঝালকাঠি' => 'Jhalokathi Jhalakathi',
        ],
        'সিলেট বিভাগ' => [
            'সিলেট' => 'Sylhet', 'মৌলভীবাজার' => 'Moulvibazar', 'হবিগঞ্জ' => 'Habiganj', 'সুনামগঞ্জ' => 'Sunamganj',
        ],
        'রংপুর বিভাগ' => [
            'রংপুর' => 'Rangpur', 'দিনাজপুর' => 'Dinajpur', 'ঠাকুরগাঁও' => 'Thakurgaon', 'পঞ্চগড়' => 'Panchagarh',
            'নীলফামারী' => 'Nilphamari', 'লালমনিরহাট' => 'Lalmonirhat', 'কুড়িগ্রাম' => 'Kurigram', 'গাইবান্ধা' => 'Gaibandha',
        ],
        'ময়মনসিংহ বিভাগ' => [
            'ময়মনসিংহ' => 'Mymensingh', 'জামালপুর' => 'Jamalpur', 'শেরপুর' => 'Sherpur', 'নেত্রকোনা' => 'Netrokona',
        ],
    ],

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
