<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'NAFIAN15'],
            [
                'description' => 'সব পণ্যে প্রযোজ্য',
                'type' => 'percentage',
                'value' => 15,
                'min_order_amount' => 2000,
                'max_discount_amount' => 500,
                'max_uses' => 500,
                'is_active' => true,
                'valid_from' => now(),
                'valid_until' => now()->addDays(8),
            ],
        );

        Coupon::updateOrCreate(
            ['code' => 'COMBO30'],
            [
                'description' => 'সব আতরে প্রযোজ্য',
                'type' => 'second_item_percentage',
                'value' => 30,
                'min_order_amount' => 0,
                'max_uses' => null,
                'is_active' => true,
                'valid_from' => now(),
                'valid_until' => now()->addDays(8),
            ],
        );

        Coupon::updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'type' => 'percentage',
                'value' => 10,
                'min_order_amount' => 1000,
                'max_uses' => null,
                'is_active' => true,
                'valid_from' => now(),
                'valid_until' => now()->addYear(),
            ],
        );

        Coupon::updateOrCreate(
            ['code' => 'FLAT50'],
            [
                'type' => 'fixed',
                'value' => 50,
                'min_order_amount' => 500,
                'max_uses' => 100,
                'is_active' => true,
                'valid_from' => now(),
                'valid_until' => now()->addMonths(6),
            ],
        );
    }
}
