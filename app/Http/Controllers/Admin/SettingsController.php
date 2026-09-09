<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:100'],
            'support_email' => ['required', 'email', 'max:150'],
            'support_phone' => ['nullable', 'string', 'max:40'],
            'currency' => ['required', 'in:USD,BDT'],
            'delivery_inside' => ['required', 'numeric', 'min:0'],
            'delivery_outside' => ['required', 'numeric', 'min:0'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:1024'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'messenger' => ['nullable', 'string', 'max:120'],
        ]);

        $current = $this->settings->group('general');
        $logoPath = $current['logo'] ?? null;

        if ($request->hasFile('logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = $request->file('logo')->store('branding', 'public');
        } elseif ($request->boolean('remove_logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = null;
        }

        $data['logo'] = $logoPath;

        $this->settings->put('general', $data);
        $this->log('settings.general', 'Updated store profile & delivery charges');

        return back()->with('success', 'General settings saved.');
    }

    public function updatePixels(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fb_enabled' => ['boolean'],
            'fb_pixel' => ['nullable', 'string', 'max:64'],
            'fb_token' => ['nullable', 'string', 'max:512'],
            'ga4_enabled' => ['boolean'],
            'ga4' => ['nullable', 'string', 'max:32'],
            'gtm' => ['nullable', 'string', 'max:32'],
            'tiktok_enabled' => ['boolean'],
            'tiktok' => ['nullable', 'string', 'max:64'],
        ]);

        $data['fb_enabled'] = $request->boolean('fb_enabled');
        $data['ga4_enabled'] = $request->boolean('ga4_enabled');
        $data['tiktok_enabled'] = $request->boolean('tiktok_enabled');

        $this->settings->put('pixels', $data);
        $this->log('settings.pixels', 'Updated marketing pixels');

        return back()->with('success', 'Pixel settings saved.');
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

        return back()->with('success', 'Courier settings saved.');
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
