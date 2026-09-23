<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\SettingsService;
use Database\Seeders\AttributeSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnalyticsTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CategorySeeder::class, AttributeSeeder::class, ProductSeeder::class]);
        Http::fake(['*' => Http::response(['events_received' => 1, 'code' => 0])]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function enablePixels(array $overrides = []): void
    {
        app(SettingsService::class)->put('pixels', array_merge([
            'fb_enabled' => true, 'fb_pixel' => '111222333',
            'ga4_enabled' => true, 'ga4' => 'G-TEST123', 'gtm' => 'GTM-ABC123',
            'tiktok_enabled' => true, 'tiktok' => 'CTESTPIXEL',
        ], $overrides));
    }

    private function variant(): ProductVariant
    {
        return ProductVariant::whereHas('product', fn ($q) => $q->where('slug', 'pebbled-card-holder'))->firstOrFail();
    }

    private function placeOrder(): Order
    {
        $this->post('/cart', ['variant_id' => $this->variant()->id]);
        $this->post('/checkout', [
            'name' => 'Nusrat Jahan',
            'phone' => '01712345678',
            'email' => 'Nusrat@Example.com',
            'address' => 'House 42, Road 11',
            'city' => 'ঢাকা',
            'area' => 'বনানী',
            'payment_method' => 'cod',
        ])->assertRedirect();

        return Order::latest('id')->firstOrFail();
    }

    public function test_nothing_is_queued_when_no_pixel_is_enabled(): void
    {
        $this->get('/product/pebbled-card-holder')
            ->assertOk()
            ->assertSee('window.nfTrack', false)
            ->assertSessionMissing('analytics.events');

        Http::assertNothingSent();
    }

    public function test_product_page_emits_view_item(): void
    {
        $this->enablePixels();

        $this->get('/product/pebbled-card-holder')
            ->assertOk()
            ->assertSee('"name":"view_item"', false)
            ->assertSee('Pebbled Card Holder');
    }

    public function test_shop_search_emits_search_and_item_list(): void
    {
        $this->enablePixels();

        $this->get('/shop?q=Merino')
            ->assertOk()
            ->assertSee('"name":"search"', false)
            ->assertSee('"search_term":"Merino"', false)
            ->assertSee('"name":"view_item_list"', false);
    }

    public function test_ajax_add_to_cart_returns_the_event(): void
    {
        $this->enablePixels();
        $variant = $this->variant();

        $response = $this->postJson('/cart', ['variant_id' => $variant->id, 'quantity' => 2])->assertOk();

        $event = $response->json('analytics.0');
        $this->assertSame('add_to_cart', $event['name']);
        $this->assertSame('BDT', $event['params']['currency']);
        $this->assertSame(2, $event['params']['items'][0]['quantity']);
        $this->assertEquals((float) $variant->price * 2, $event['params']['value']);
        $this->assertSame((string) $variant->product_id, $event['params']['items'][0]['item_id']);
    }

    public function test_checkout_emits_begin_checkout(): void
    {
        $this->enablePixels();
        $this->post('/cart', ['variant_id' => $this->variant()->id]);
        $this->get('/product/pebbled-card-holder'); // flushes add_to_cart

        $this->get('/checkout')->assertOk()->assertSee('"name":"begin_checkout"', false);
    }

    public function test_purchase_is_emitted_once_on_the_confirmation_page(): void
    {
        $this->enablePixels();
        $order = $this->placeOrder();

        $this->get(route('store.order.confirmation', $order->order_number))
            ->assertOk()
            ->assertSee('"name":"purchase"', false)
            ->assertSee('"transaction_id":"'.$order->order_number.'"', false)
            ->assertSee('"id":"purchase.'.$order->order_number.'"', false);

        $this->get(route('store.order.confirmation', $order->order_number))
            ->assertOk()
            ->assertDontSee('"name":"purchase"', false);
    }

    public function test_purchase_is_sent_to_meta_conversions_api_with_hashed_user_data(): void
    {
        $this->enablePixels(['fb_token' => 'EAAG-secret', 'fb_test_code' => 'TEST42']);
        $order = $this->placeOrder();

        Http::assertSent(function (Request $request) use ($order): bool {
            if (! str_contains($request->url(), 'graph.facebook.com') || ($request['data'][0]['event_name'] ?? null) !== 'Purchase') {
                return false;
            }

            $event = $request['data'][0];

            return str_contains($request->url(), '/111222333/events')
                && str_contains($request->url(), 'access_token=EAAG-secret')
                && $request['test_event_code'] === 'TEST42'
                && $event['event_id'] === 'purchase.'.$order->order_number
                && $event['user_data']['em'] === [hash('sha256', 'nusrat@example.com')]
                && $event['user_data']['ph'] === [hash('sha256', '8801712345678')]
                && ((array) $event['custom_data'])['order_id'] === $order->order_number
                && ((array) $event['custom_data'])['currency'] === 'BDT';
        });
    }

    public function test_purchase_is_sent_to_tiktok_and_ga4_server_side(): void
    {
        $this->enablePixels(['tiktok_token' => 'tt-token', 'ga4_api_secret' => 'ga-secret']);
        $order = $this->placeOrder();

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'business-api.tiktok.com')
            && $request->header('Access-Token') === ['tt-token']
            && $request['data'][0]['event'] === 'CompletePayment'
            && $request['data'][0]['event_id'] === 'purchase.'.$order->order_number);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'google-analytics.com/mp/collect')
            && str_contains($request->url(), 'api_secret=ga-secret')
            && $request['events'][0]['name'] === 'purchase'
            && $request['events'][0]['params']['transaction_id'] === $order->order_number);
    }

    public function test_server_side_events_are_skipped_without_a_token(): void
    {
        $this->enablePixels();
        $this->placeOrder();

        Http::assertNothingSent();
    }

    public function test_newsletter_signup_emits_a_lead(): void
    {
        $this->enablePixels();

        $this->postJson(route('store.subscribe'), ['source' => 'newsletter', 'contact' => 'new@example.com'])
            ->assertOk()
            ->assertJsonPath('analytics.0.name', 'generate_lead');
    }

    public function test_registration_emits_sign_up(): void
    {
        $this->enablePixels();

        $this->post('/register', [
            'name' => 'Rafi', 'email' => 'rafi@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect();

        $this->get('/shop')->assertSee('"name":"sign_up"', false);
    }

    public function test_admin_pixel_secrets_are_encrypted_kept_when_blank_and_clearable(): void
    {
        $admin = Admin::factory()->create();
        $payload = ['fb_enabled' => 1, 'fb_pixel' => '111222333', 'ga4' => 'G-TEST123', 'gtm' => 'GTM-ABC123'];

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.pixels'), $payload + ['fb_token' => 'EAAG-secret', 'tiktok_token' => 'tt-token'])
            ->assertSessionHasNoErrors();

        $raw = Setting::where('group', 'pixels')->firstOrFail()->value;
        $this->assertNotSame('EAAG-secret', $raw['fb_token']);

        $this->actingAs($admin, 'admin')->put(route('admin.settings.pixels'), $payload);
        $this->assertSame('EAAG-secret', app(SettingsService::class)->group('pixels')['fb_token']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.index'))
            ->assertDontSee('EAAG-secret');

        $this->actingAs($admin, 'admin')->put(route('admin.settings.pixels'), $payload + ['clear_secrets' => ['fb_token']]);
        $pixels = app(SettingsService::class)->group('pixels');
        $this->assertSame('', $pixels['fb_token']);
        $this->assertSame('tt-token', $pixels['tiktok_token']);
    }

    public function test_invalid_ga4_id_is_rejected(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->put(route('admin.settings.pixels'), ['ga4' => 'UA-12345'])
            ->assertSessionHasErrors('ga4');
    }
}
