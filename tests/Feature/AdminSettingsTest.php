<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::factory()->create();
    }

    public function test_settings_page_renders_with_defaults(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('স্টোরের তথ্য')
            ->assertSee('ডেলিভারি চার্জ')
            ->assertSee('Steadfast Courier')
            ->assertSee('Facebook / Meta Pixel');
    }

    public function test_general_settings_persist(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.settings.general'), [
                'store_name' => 'Nafian Studio',
                'support_email' => 'team@nafian.com',
                'support_phone' => '+880 1700 111222',
                'currency' => 'USD',
                'delivery_inside' => 70,
                'delivery_outside' => 130,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $general = app(SettingsService::class)->group('general');
        $this->assertSame('Nafian Studio', $general['store_name']);
        $this->assertSame('team@nafian.com', $general['support_email']);
    }

    public function test_brand_logo_uploads_and_persists(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.settings.general'), [
                'store_name' => 'Nafian',
                'support_email' => 'team@nafian.com',
                'support_phone' => '+880 1700 111222',
                'currency' => 'BDT',
                'delivery_inside' => 60,
                'delivery_outside' => 120,
                'logo' => UploadedFile::fake()->image('logo.png', 200, 80),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $logo = app(SettingsService::class)->group('general')['logo'];
        $this->assertNotNull($logo);
        Storage::disk('public')->assertExists($logo);
    }

    public function test_brand_logo_can_be_removed(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('old.png')->store('branding', 'public');
        app(SettingsService::class)->put('general', ['logo' => $path]);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.settings.general'), [
                'store_name' => 'Nafian',
                'support_email' => 'team@nafian.com',
                'support_phone' => '+880 1700 111222',
                'currency' => 'BDT',
                'delivery_inside' => 60,
                'delivery_outside' => 120,
                'remove_logo' => '1',
            ])
            ->assertRedirect();

        $this->assertNull(app(SettingsService::class)->group('general')['logo']);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_courier_secrets_are_encrypted_at_rest(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.settings.courier'), [
                'steadfast_enabled' => '1',
                'api_key' => 'PUBLIC_KEY_123',
                'secret_key' => 'SUPER_SECRET_999',
                'steadfast_base_url' => 'https://portal.steadfast.com.bd/api/v1',
                'pathao_base_url' => 'https://api-hermes.pathao.com',
            ])
            ->assertSessionHas('success');

        // Raw stored value must not contain the plaintext secret.
        $raw = Setting::where('group', 'courier')->value('value');
        $this->assertStringNotContainsString('SUPER_SECRET_999', json_encode($raw));

        // Service round-trips it back to plaintext.
        $courier = app(SettingsService::class)->group('courier');
        $this->assertSame('SUPER_SECRET_999', $courier['secret_key']);
        $this->assertTrue($courier['steadfast_enabled']);
    }

    public function test_blank_secret_keeps_existing_value(): void
    {
        $service = app(SettingsService::class);
        $service->put('courier', ['secret_key' => 'KEEP_ME', 'steadfast_enabled' => true]);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.settings.courier'), [
                'steadfast_enabled' => '1',
                'api_key' => 'NEW_PUBLIC',
                'secret_key' => '',
            ])
            ->assertSessionHas('success');

        $this->assertSame('KEEP_ME', $service->group('courier')['secret_key']);
    }

    public function test_enabled_pixel_renders_on_storefront(): void
    {
        app(SettingsService::class)->put('pixels', [
            'fb_enabled' => true,
            'fb_pixel' => '1234567890',
        ]);

        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('fbq(', false)
            ->assertSee('1234567890', false);
    }

    public function test_disabled_pixel_does_not_render(): void
    {
        app(SettingsService::class)->put('pixels', [
            'fb_enabled' => false,
            'fb_pixel' => '1234567890',
        ]);

        $this->get(route('store.home'))
            ->assertOk()
            ->assertDontSee('fbq(', false);
    }
}
