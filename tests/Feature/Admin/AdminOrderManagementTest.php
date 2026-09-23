<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Admin;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create(['role' => UserRole::SuperAdmin]);
    }

    private function orderWithItem(): Order
    {
        $variant = ProductVariant::factory()->stock(10)->create(['price' => 1450]);
        $order = Order::factory()->create(['payment_method' => 'cod', 'total_amount' => 1529]);

        OrderItem::create([
            'order_id' => $order->id,
            'variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'variant_name' => $variant->display_name,
            'sku' => $variant->sku,
            'unit_price' => $variant->price,
            'quantity' => 1,
            'line_total' => $variant->price,
        ]);

        return $order;
    }

    public function test_dashboard_shows_kpis_and_recent_orders(): void
    {
        $order = $this->orderWithItem();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('মোট বিক্রি')
            ->assertSee('দৈনিক বিক্রি')
            ->assertSee('সাম্প্রতিক অর্ডার')
            ->assertSee(bn_digits($order->order_number));
    }

    public function test_dashboard_order_tab_filters_by_status(): void
    {
        $pending = Order::factory()->create(['status' => OrderStatus::Pending]);
        $shipped = Order::factory()->create(['status' => OrderStatus::Shipped]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard', ['tab' => 'shipped']))
            ->assertOk()
            ->assertSee(bn_digits($shipped->order_number))
            ->assertDontSee(bn_digits($pending->order_number));
    }

    public function test_admin_can_store_rider_details_and_an_internal_note(): void
    {
        $order = $this->orderWithItem();

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.orders.details', $order), [
                'rider_name' => 'শাকিল আহমেদ',
                'rider_phone' => '০১৯০০ ১১২ ২৩৩',
                'admin_notes' => 'বিকেল ৫টার পরে ডেলিভারি',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('শাকিল আহমেদ', $order->rider_name);
        $this->assertSame('01900112233', $order->rider_phone);
        $this->assertSame('বিকেল ৫টার পরে ডেলিভারি', $order->admin_notes);
    }

    public function test_status_change_is_written_to_the_order_history(): void
    {
        $order = $this->orderWithItem();

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.orders.update-status', $order), ['status' => OrderStatus::Confirmed->value])
            ->assertRedirect();

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => OrderStatus::Confirmed->value,
            'admin_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('অর্ডারের ইতিহাস')
            ->assertSee('নিশ্চিত');
    }

    public function test_invoice_renders_for_printing(): void
    {
        $order = $this->orderWithItem();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.invoice', $order))
            ->assertOk()
            ->assertSee('ইনভয়েস')
            ->assertSee(bn_digits($order->order_number));
    }

    public function test_campaign_settings_persist_and_drive_the_offers_page(): void
    {
        Coupon::factory()->create(['code' => 'COMBO30', 'is_active' => true, 'valid_until' => now()->addDays(8)]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.campaign'), [
                'enabled' => '1',
                'eyebrow' => 'সেপ্টেম্বর ক্যাম্পেইন',
                'title' => 'দুটি নিলে দ্বিতীয়টিতে ৩০% ছাড়',
                'body' => 'সব সুগন্ধিতে প্রযোজ্য।',
                'coupon_code' => 'combo30',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $campaign = app(SettingsService::class)->group('campaign');
        $this->assertSame('COMBO30', $campaign['coupon_code']);

        $this->get('/offers')
            ->assertOk()
            ->assertSee('দুটি নিলে দ্বিতীয়টিতে ৩০% ছাড়')
            ->assertSee('COMBO30');
    }

    public function test_campaign_settings_reject_an_unknown_coupon(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.campaign'), ['title' => 'অফার', 'coupon_code' => 'NOPE'])
            ->assertSessionHasErrors('coupon_code');
    }

    public function test_general_settings_store_delivery_and_payment_toggles(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.general'), [
                'store_name' => 'Nafian',
                'support_email' => 'hello@nafian.com',
                'support_phone' => '+880 1712 345 678',
                'store_address' => 'রোড ১১, বনানী, ঢাকা',
                'currency' => 'BDT',
                'delivery_inside' => 79,
                'delivery_outside' => 149,
                'free_delivery_threshold' => 3000,
                'cod_enabled' => '1',
                'mobile_banking_enabled' => '0',
                'card_enabled' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $general = app(SettingsService::class)->group('general');
        $this->assertSame(79, (int) $general['delivery_inside']);
        $this->assertSame(3000, (int) $general['free_delivery_threshold']);
        $this->assertTrue($general['cod_enabled']);
        $this->assertFalse($general['mobile_banking_enabled']);
    }
}
