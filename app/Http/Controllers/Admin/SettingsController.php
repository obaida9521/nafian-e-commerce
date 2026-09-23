<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\MediaLibraryService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.settings.index', [
            'tab' => $request->string('tab', 'general')->toString(),
            'general' => $this->settings->group('general'),
            'pixels' => $this->settings->group('pixels'),
            'courier' => $this->settings->group('courier'),
            'campaign' => $this->settings->group('campaign'),
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:100'],
            'support_email' => ['required', 'email', 'max:150'],
            'support_phone' => ['nullable', 'string', 'max:40'],
            'store_address' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'in:USD,BDT'],
            'delivery_inside' => ['required', 'numeric', 'min:0'],
            'delivery_outside' => ['required', 'numeric', 'min:0'],
            'free_delivery_threshold' => ['nullable', 'numeric', 'min:0'],
            'instagram' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:1024'],
            'library_logo' => ['nullable', 'string', 'regex:'.MediaLibraryService::KEY_PATTERN],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'messenger' => ['nullable', 'string', 'max:120'],
        ]);

        $current = $this->settings->group('general');
        $logoPath = $current['logo'] ?? null;

        // Toggles are only touched when the form sent them (hidden 0 + checkbox 1).
        foreach (['cod_enabled', 'mobile_banking_enabled', 'card_enabled', 'order_sms', 'low_stock_alert'] as $toggle) {
            $data[$toggle] = $request->has($toggle) ? $request->boolean($toggle) : $current[$toggle];
        }

        $data['free_delivery_threshold'] = $data['free_delivery_threshold'] ?? $current['free_delivery_threshold'];

        if ($request->hasFile('logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = $request->file('logo')->store('branding', 'public');
        } elseif ($request->filled('library_logo') && $request->input('library_logo') !== 'logo'
            && ($copied = app(MediaLibraryService::class)->copyToDirectory($request->string('library_logo')->toString(), 'branding'))) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = $copied;
        } elseif ($request->boolean('remove_logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = null;
        }

        unset($data['library_logo']);
        $data['logo'] = $logoPath;

        $this->settings->put('general', array_merge($current, $data));
        $this->log('settings.general', 'Updated store profile & delivery charges');

        return back()->with('success', 'সেটিংস সংরক্ষণ হয়েছে।');
    }

    public function updateCampaign(Request $request): RedirectResponse
    {
        $request->merge(['coupon_code' => strtoupper(trim((string) $request->input('coupon_code')))]);

        $data = $request->validate([
            'eyebrow' => ['nullable', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:300'],
            'coupon_code' => ['nullable', 'string', 'max:50', 'exists:coupons,code'],
        ], [], ['coupon_code' => 'কুপন কোড']);

        $data['enabled'] = $request->boolean('enabled');
        $data['coupon_code'] = filled($data['coupon_code'] ?? null) ? $data['coupon_code'] : null;

        $this->settings->put('campaign', $data);
        $this->log('settings.campaign', 'Updated offers campaign');

        return back()->with('success', 'ক্যাম্পেইন সংরক্ষণ হয়েছে।');
    }

    public function updatePixels(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fb_enabled' => ['boolean'],
            'fb_pixel' => ['nullable', 'string', 'max:64'],
            'fb_token' => ['nullable', 'string', 'max:512'],
            'fb_test_code' => ['nullable', 'string', 'max:32'],
            'ga4_enabled' => ['boolean'],
            'ga4' => ['nullable', 'string', 'max:32', 'regex:/^G-[A-Z0-9]+$/i'],
            'gtm' => ['nullable', 'string', 'max:32', 'regex:/^GTM-[A-Z0-9]+$/i'],
            'ga4_api_secret' => ['nullable', 'string', 'max:128'],
            'tiktok_enabled' => ['boolean'],
            'tiktok' => ['nullable', 'string', 'max:64'],
            'tiktok_token' => ['nullable', 'string', 'max:512'],
            'tiktok_test_code' => ['nullable', 'string', 'max:32'],
            'clear_secrets' => ['nullable', 'array'],
            'clear_secrets.*' => [Rule::in(['fb_token', 'ga4_api_secret', 'tiktok_token'])],
        ]);

        // Blank secret fields keep the stored value; "clear_secrets[]" removes one on purpose.
        $current = $this->settings->group('pixels');
        $clear = $data['clear_secrets'] ?? [];
        unset($data['clear_secrets']);
        foreach (['fb_token', 'ga4_api_secret', 'tiktok_token'] as $secret) {
            if (in_array($secret, $clear, true)) {
                $data[$secret] = '';
            } elseif (empty($data[$secret])) {
                $data[$secret] = $current[$secret] ?? '';
            }
        }

        $data['fb_enabled'] = $request->boolean('fb_enabled');
        $data['ga4_enabled'] = $request->boolean('ga4_enabled');
        $data['tiktok_enabled'] = $request->boolean('tiktok_enabled');

        $this->settings->put('pixels', $data);
        $this->log('settings.pixels', 'Updated marketing pixels');

        return back()->with('success', 'পিক্সেল সেটিংস সংরক্ষণ হয়েছে।');
    }

    public function updateCourier(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'steadfast_enabled' => ['boolean'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:255'],
            'steadfast_base_url' => ['nullable', 'url', 'max:255'],
            'webhook' => ['nullable', 'url', 'max:255'],
            'pathao_enabled' => ['boolean'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:150'],
            'password' => ['nullable', 'string', 'max:255'],
            'store_id' => ['nullable', 'string', 'max:64'],
            'pathao_base_url' => ['nullable', 'url', 'max:255'],
            'pathao_sandbox' => ['boolean'],
        ]);

        // Preserve existing secrets when the field is submitted blank.
        $current = $this->settings->group('courier');
        foreach (['secret_key', 'client_secret', 'password'] as $secret) {
            if (empty($data[$secret])) {
                $data[$secret] = $current[$secret] ?? '';
            }
        }

        $data['steadfast_enabled'] = $request->boolean('steadfast_enabled');
        $data['pathao_enabled'] = $request->boolean('pathao_enabled');
        $data['pathao_sandbox'] = $request->boolean('pathao_sandbox');

        $this->settings->put('courier', $data);
        $this->log('settings.courier', 'Updated courier integrations');

        return back()->with('success', 'কুরিয়ার সেটিংস সংরক্ষণ হয়েছে।');
    }

    private function log(string $action, string $description): void
    {
        $this->activityLogger->log(
            auth('admin')->user(),
            $action,
            'settings',
            null,
            $description,
        );
    }
}
