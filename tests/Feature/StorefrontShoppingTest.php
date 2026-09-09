<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\AttributeSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CouponSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontShoppingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            CategorySeeder::class,
            AttributeSeeder::class,
            ProductSeeder::class,
            CouponSeeder::class,
        ]);
    }

    public function test_home_lists_featured_products(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Considered pieces')
            ->assertSee('Marlowe Structured Tote');
    }

    public function test_plp_filters_by_category(): void
    {
        $this->get('/shop/bags')
            ->assertOk()
            ->assertSee('Marlowe Structured Tote')
            ->assertDontSee('Atelier Wool Overcoat');
    }

    public function test_pdp_renders_with_variants(): void
    {
        $product = Product::where('slug', 'marlowe-structured-tote')->firstOrFail();

        $this->get("/product/{$product->slug}")
            ->assertOk()
            ->assertSee('Add to bag');
    }

    public function test_choosing_a_variant_puts_an_item_in_the_session_cart(): void
    {
        $variant = ProductVariant::whereHas('product', fn ($q) => $q->where('slug', 'pebbled-card-holder'))->first();

        $this->post('/cart', ['variant_id' => $variant->id])
            ->assertRedirect()
            ->assertSessionHas('open_cart');

        $cart = session('cart');
        $this->assertNotEmpty($cart);
        $this->assertSame(1, (int) array_sum($cart));
    }

    public function test_multi_variant_product_cannot_be_added_without_a_selection(): void
    {
        // Card holder has multiple colour variants; a bare product_id must not
        // silently pick one — the shopper has to choose on the card or PDP.
        $product = Product::where('slug', 'pebbled-card-holder')->firstOrFail();

        $this->post('/cart', ['product_id' => $product->id])
            ->assertSessionHas('error');

        $this->assertEmpty(session('cart'));
    }

    public function test_guest_can_checkout_and_create_an_order(): void
    {
        $variant = ProductVariant::whereHas('product', fn ($q) => $q->where('slug', 'pebbled-card-holder'))->first();

        $this->post('/cart', ['variant_id' => $variant->id, 'quantity' => 2]);

        $response = $this->post('/checkout', [
            'email' => 'guest@example.com',
            'first_name' => 'Amara',
            'last_name' => 'Osei',
            'address' => '48 Linden Ave',
            'apt' => '7C',
            'city' => 'Brooklyn',
            'zip' => '11201',
            'phone' => '+1 415 552 0192',
            'payment_method' => 'cod',
        ]);

        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('store.order.confirmation', $order->order_number));

        $this->assertSame('guest@example.com', $order->guest_email);
        $this->assertSame(2, $order->items->sum('quantity'));
        $this->assertEmpty(session('cart'));

        // Stock was reserved, not yet deducted.
        $this->assertSame(2, $variant->fresh()->reserved_quantity);
    }

    public function test_out_of_stock_product_cannot_be_added(): void
    {
        $product = Product::where('slug', 'silk-twill-shirt')->firstOrFail();

        $this->post('/cart', ['product_id' => $product->id])
            ->assertSessionHas('error');

        $this->assertEmpty(session('cart'));
    }

    public function test_customer_can_add_and_default_an_address(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $this->post('/account/addresses', [
            'recipient_name' => 'Amara Osei',
            'phone' => '+1 415 552 0192',
            'address_line1' => '48 Linden Ave',
            'city' => 'Brooklyn',
            'district' => 'NY',
            'is_default' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'recipient_name' => 'Amara Osei',
            'is_default' => true,
        ]);
    }

    public function test_customer_cannot_edit_another_users_address(): void
    {
        $owner = User::factory()->create();
        $address = $owner->addresses()->create([
            'recipient_name' => 'Owner', 'phone' => '1', 'address_line1' => 'X',
            'city' => 'C', 'district' => 'D',
        ]);

        $this->actingAs(User::factory()->create(), 'web')
            ->delete("/account/addresses/{$address->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('addresses', ['id' => $address->id]);
    }

    public function test_guest_can_track_order_with_number_and_email(): void
    {
        $variant = ProductVariant::whereHas('product', fn ($q) => $q->where('slug', 'pebbled-card-holder'))->first();
        $this->post('/cart', ['variant_id' => $variant->id]);
        $this->post('/checkout', [
            'email' => 'track@example.com',
            'first_name' => 'Track', 'last_name' => 'Me',
            'address' => '1 St', 'city' => 'Town', 'zip' => '1000',
            'phone' => '123', 'payment_method' => 'cod',
        ]);

        $order = Order::latest('id')->first();

        $this->post('/track', [
            'order_number' => $order->order_number,
            'email' => 'track@example.com',
        ])->assertOk()->assertSee($order->order_number)->assertSee('Order placed');

        // Wrong email reveals nothing.
        $this->post('/track', [
            'order_number' => $order->order_number,
            'email' => 'wrong@example.com',
        ])->assertOk()->assertSee('No order found');
    }

    public function test_registered_customer_sees_their_orders(): void
    {
        $user = User::factory()->create();
        $variant = ProductVariant::whereHas('product', fn ($q) => $q->where('slug', 'pebbled-card-holder'))->first();

        $this->actingAs($user, 'web');
        $this->post('/cart', ['variant_id' => $variant->id]);
        $this->post('/checkout', [
            'email' => $user->email,
            'first_name' => 'Test', 'last_name' => 'User',
            'address' => '1 St', 'city' => 'Town', 'zip' => '1000',
            'phone' => '123', 'payment_method' => 'online',
        ]);

        $this->get('/account')
            ->assertOk()
            ->assertSee('NF-'.date('Y'));
    }
}
