<?php

namespace Tests\Feature\Services;

use App\Exceptions\CouponException;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    use RefreshDatabase;

    private CouponService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CouponService::class);
    }

    public function test_valid_percentage_coupon_passes(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'SAVE10', 'value' => 10]);

        $result = $this->service->validate('SAVE10', 2000);

        $this->assertTrue($coupon->is($result));
    }

    public function test_percentage_discount_is_calculated(): void
    {
        $coupon = Coupon::factory()->create(['value' => 10]);
        $this->assertSame(200.0, $this->service->calculateDiscount($coupon, 2000));
    }

    public function test_fixed_discount_caps_at_subtotal(): void
    {
        $coupon = Coupon::factory()->fixed(500)->create();
        $this->assertSame(300.0, $this->service->calculateDiscount($coupon, 300));
    }

    public function test_unknown_code_rejected(): void
    {
        $this->expectException(CouponException::class);
        $this->service->validate('NOPE', 1000);
    }

    public function test_expired_coupon_rejected(): void
    {
        Coupon::factory()->expired()->create(['code' => 'OLD']);
        $this->expectException(CouponException::class);
        $this->service->validate('OLD', 1000);
    }

    public function test_inactive_coupon_rejected(): void
    {
        Coupon::factory()->create(['code' => 'OFF', 'is_active' => false]);
        $this->expectException(CouponException::class);
        $this->service->validate('OFF', 1000);
    }

    public function test_maxed_out_coupon_rejected(): void
    {
        Coupon::factory()->create(['code' => 'MAX', 'max_uses' => 5, 'used_count' => 5]);
        $this->expectException(CouponException::class);
        $this->service->validate('MAX', 1000);
    }

    public function test_below_minimum_rejected(): void
    {
        Coupon::factory()->create(['code' => 'MIN', 'min_order_amount' => 1500]);
        $this->expectException(CouponException::class);
        $this->service->validate('MIN', 1000);
    }
}
