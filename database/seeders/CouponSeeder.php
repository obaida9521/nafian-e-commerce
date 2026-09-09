<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
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
