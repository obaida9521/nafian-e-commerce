<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingsService
{
    /**
     * Field names whose values are encrypted at rest.
     *
     * @var list<string>
     */
    private const SECRET_FIELDS = [
        'fb_token', 'secret_key', 'client_secret', 'password',
    ];

    /**
     * Default values per group, so the UI always has something to bind to.
     *
     * @var array<string, array<string, mixed>>
     */
    private const DEFAULTS = [
        'general' => [
            'store_name' => 'Nafian',
            'support_email' => 'hello@nafian.com',
            'support_phone' => '+880 1700 000000',
            'currency' => 'BDT',
            'delivery_inside' => 60,
            'delivery_outside' => 120,
            'logo' => null,
            'whatsapp' => '',
            'messenger' => '',
        ],
        'pixels' => [
            'fb_enabled' => false, 'fb_pixel' => '', 'fb_token' => '',
            'ga4_enabled' => false, 'ga4' => '', 'gtm' => '',
            'tiktok_enabled' => false, 'tiktok' => '',
        ],
        'courier' => [
            'steadfast_enabled' => false, 'api_key' => '', 'secret_key' => '',
            'steadfast_base_url' => 'https://portal.steadfast.com.bd/api/v1', 'webhook' => '',
            'pathao_enabled' => false, 'client_id' => '', 'client_secret' => '',
            'username' => '', 'password' => '', 'store_id' => '',
            'pathao_base_url' => 'https://api-hermes.pathao.com', 'pathao_sandbox' => true,
        ],
    ];

    /**
     * Fetch a settings group merged over its defaults (secrets decrypted).
     *
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        $stored = Cache::rememberForever("settings.{$group}", function () use ($group): array {
            $row = Setting::where('group', $group)->first();

            return $row?->value ?? [];
        });

        $merged = array_merge(self::DEFAULTS[$group] ?? [], $this->decrypt($stored));

        return $merged;
    }

    /**
     * Persist a settings group (secrets encrypted).
     *
     * @param  array<string, mixed>  $data
     */
    public function put(string $group, array $data): void
    {
        Setting::updateOrCreate(
            ['group' => $group],
            ['value' => $this->encrypt($data)],
        );

        Cache::forget("settings.{$group}");
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function encrypt(array $data): array
    {
        foreach (self::SECRET_FIELDS as $field) {
            if (! empty($data[$field])) {
                $data[$field] = Crypt::encryptString((string) $data[$field]);
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function decrypt(array $data): array
    {
        foreach (self::SECRET_FIELDS as $field) {
            if (! empty($data[$field])) {
                try {
                    $data[$field] = Crypt::decryptString((string) $data[$field]);
                } catch (\Throwable) {
                    $data[$field] = '';
                }
            }
        }

        return $data;
    }
}
