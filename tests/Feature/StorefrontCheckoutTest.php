<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Subscriber;
use App\Services\SettingsService;
use Database\Seeders\AttributeSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CategorySeeder::class, AttributeSeeder::class, ProductSeeder::class]);

        app(SettingsService::class)->put('general', [
            'delivery_inside' => 79,
            'delivery_outside' => 149,
            'free_delivery_threshold' => 3000,
            'cod_enabled' => true,
            'mobile_banking_enabled' => true,
            'card_enabled' => false,
        ]);
    }

    private function variant(string $slug = 'pebbled-card-holder'): ProductVariant
    {
        return ProductVariant::whereHas('product', fn ($q) => $q->where('slug', $slug))->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'নুসরাত জাহান',
            'phone' => '০১৭১২ ৩৪৫ ৬৭৮',
            'email' => 'nusrat@example.com',
            'address' => 'বাড়ি ৪২, রোড ১১',
            'city' => 'ঢাকা',
            'area' => 'বনানী',
            'postcode' => '১২১৩',
            'payment_method' => 'cod',
        ], $overrides);
    }

    public function test_inside_dhaka_order_uses_the_inside_delivery_charge(): void
    {
        $this->post('/cart', ['variant_id' => $this->variant()->id]);
        $this->post('/checkout', $this->checkoutPayload())->assertRedirect();

        $order = Order::latest('id')->firstOrFail();

        $this->assertSame('inside', $order->delivery_zone);
        $this->assertSame(79.0, (float) $order->delivery_charge);
        $this->assertSame('01712345678', $order->shipping_phone);
        $this->assertSame('বনানী', $order->shipping_area);
        $this->assertSame('1213', $order->shipping_postcode);
    }

    public function test_outside_dhaka_order_uses_the_outside_delivery_charge(): void
    {
        $this->post('/cart', ['variant_id' => $this->variant()->id]);
        $this->post('/checkout', $this->checkoutPayload(['city' => 'চট্টগ্রাম', 'area' => 'আগ্রাবাদ']))->assertRedirect();

        $order = Order::latest('id')->firstOrFail();

        $this->assertSame('outside', $order->delivery_zone);
        $this->assertSame(149.0, (float) $order->delivery_charge);
    }

    public function test_delivery_is_free_above_the_threshold(): void
    {
        $variant = $this->variant('atelier-wool-overcoat');
        $this->post('/cart', ['variant_id' => $variant->id, 'quantity' => 8]);
        $this->post('/checkout', $this->checkoutPayload())->assertRedirect();

        $this->assertSame(0.0, (float) Order::latest('id')->firstOrFail()->delivery_charge);
    }

    public function test_a_disabled_payment_method_is_rejected(): void
    {
        $this->post('/cart', ['variant_id' => $this->variant()->id]);

        $this->post('/checkout', $this->checkoutPayload(['payment_method' => 'card']))
            ->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Order::count());
    }

    public function test_an_invalid_phone_number_is_rejected(): void
    {
        $this->post('/cart', ['variant_id' => $this->variant()->id]);

        $this->post('/checkout', $this->checkoutPayload(['phone' => '12345']))
            ->assertSessionHasErrors('phone');
    }

    public function test_order_placement_records_the_first_status_history_entry(): void
    {
        $this->post('/cart', ['variant_id' => $this->variant()->id]);
        $this->post('/checkout', $this->checkoutPayload());

        $order = Order::latest('id')->firstOrFail();

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => OrderStatus::Pending->value,
        ]);
    }

    public function test_second_item_coupon_discounts_only_the_cheaper_unit(): void
    {
        Coupon::factory()->create([
            'code' => 'COMBO30',
            'type' => Coupon::TYPE_SECOND_ITEM,
            'value' => 30,
            'min_order_amount' => 0,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDays(8),
        ]);

        $cheap = $this->variant('pebbled-card-holder');          // 85
        $pricey = $this->variant('cashmere-ribbed-scarf');       // 120

        $this->post('/cart', ['variant_id' => $cheap->id]);
        $this->post('/cart', ['variant_id' => $pricey->id]);
        $this->post('/cart/coupon', ['code' => 'COMBO30'])->assertRedirect();

        $this->post('/checkout', $this->checkoutPayload());

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame(round((float) $cheap->price * 0.3, 2), (float) $order->discount_amount);
    }

    public function test_second_item_coupon_needs_two_units(): void
    {
        Coupon::factory()->create([
            'code' => 'COMBO30',
            'type' => Coupon::TYPE_SECOND_ITEM,
            'value' => 30,
            'min_order_amount' => 0,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDays(8),
        ]);

        $this->post('/cart', ['variant_id' => $this->variant()->id]);

        $this->post('/cart/coupon', ['code' => 'COMBO30'])->assertSessionHas('error');
    }

    public function test_buy_now_sends_the_shopper_straight_to_checkout(): void
    {
        $this->post('/cart', ['variant_id' => $this->variant()->id, 'buy_now' => 1])
            ->assertRedirect(route('store.checkout'));
    }

    public function test_shopper_can_cancel_a_pending_order_from_tracking(): void
    {
        $variant = $this->variant();
        $this->post('/cart', ['variant_id' => $variant->id]);
        $this->post('/checkout', $this->checkoutPayload());

        $order = Order::latest('id')->firstOrFail();

        $this->post(route('store.track.cancel', $order->order_number))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(0, $variant->fresh()->reserved_quantity);
    }

    public function test_tracking_cancel_is_blocked_for_other_visitors(): void
    {
        $this->post('/cart', ['variant_id' => $this->variant()->id]);
        $this->post('/checkout', $this->checkoutPayload());
        $order = Order::latest('id')->firstOrFail();

        $this->flushSession();

        $this->post(route('store.track.cancel', $order->order_number))->assertForbidden();
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_search_suggestions_return_matching_products(): void
    {
        $this->getJson('/search/suggest?q=cashmere')
            ->assertOk()
            ->assertJsonPath('products.0.name', 'Cashmere Ribbed Scarf');
    }

    public function test_newsletter_signup_stores_a_subscriber(): void
    {
        $this->post('/subscribe', ['source' => 'newsletter', 'contact' => 'Hello@Example.com'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('subscribers', ['contact' => 'hello@example.com', 'channel' => 'email']);
    }

    public function test_campaign_sms_signup_validates_the_number(): void
    {
        $this->postJson('/subscribe', ['source' => 'campaign_sms', 'contact' => '123'])
            ->assertUnprocessable();

        $this->postJson('/subscribe', ['source' => 'campaign_sms', 'contact' => '০১৭১২৩৪৫৬৭৮'])->assertOk();

        $this->assertDatabaseHas('subscribers', ['contact' => '01712345678', 'channel' => 'sms']);
        $this->assertSame(1, Subscriber::count());
    }

    public function test_offers_page_lists_combos_and_deals(): void
    {
        $combo = Product::where('slug', 'pebbled-card-holder')->firstOrFail();
        $combo->update(['is_combo' => true]);

        $this->get('/offers')
            ->assertOk()
            ->assertSee('Pebbled Card Holder')
            ->assertSee('ছাড় চলছে');
    }

    public function test_products_hidden_when_out_of_stock_drop_out_of_the_catalogue(): void
    {
        $product = Product::where('slug', 'silk-twill-shirt')->firstOrFail();

        $this->get('/shop')->assertSee('Silk Twill Shirt');

        $product->update(['hide_when_out_of_stock' => true]);

        $this->get('/shop')->assertDontSee('Silk Twill Shirt');
    }
}
