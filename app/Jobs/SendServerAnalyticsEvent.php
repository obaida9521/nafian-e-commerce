<?php

namespace App\Jobs;

use App\Services\AnalyticsService;
use App\Services\SettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends one storefront event to the server-side ad/analytics APIs:
 * Meta Conversions API, TikTok Events API and GA4 Measurement Protocol.
 * Each platform is independent — one failing is logged and does not block the others.
 */
class SendServerAnalyticsEvent implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $params  GA4-style event parameters
     * @param  array{ip: ?string, user_agent: ?string, url: string, fbp: ?string, fbc: ?string, ttp: ?string, ttclid: ?string, ga_client_id: string}  $context
     * @param  array<string, string>  $user  SHA-256 hashed user data
     */
    public function __construct(
        public string $name,
        public array $params,
        public string $eventId,
        public array $context,
        public array $user,
        public int $timestamp,
    ) {}

    public function handle(SettingsService $settings, AnalyticsService $analytics): void
    {
        $px = $settings->group('pixels');
        $platforms = $analytics->serverPlatforms();
        $map = AnalyticsService::EVENT_MAP[$this->name] ?? [];

        if ($platforms['meta'] && isset($map['meta'])) {
            $this->attempt('meta', fn () => $this->sendToMeta($px, $map['meta']));
        }

        if ($platforms['tiktok'] && isset($map['tiktok'])) {
            $this->attempt('tiktok', fn () => $this->sendToTikTok($px, $map['tiktok']));
        }

        if ($platforms['ga4'] && $this->name === 'purchase') {
            $this->attempt('ga4', fn () => $this->sendToGa4($px));
        }
    }

    /**
     * @param  array<string, mixed>  $px
     */
    private function sendToMeta(array $px, string $eventName): void
    {
        $items = $this->params['items'] ?? [];

        $userData = array_filter([
            'client_ip_address' => $this->context['ip'],
            'client_user_agent' => $this->context['user_agent'],
            'fbp' => $this->context['fbp'],
            'fbc' => $this->context['fbc'],
            'em' => isset($this->user['email']) ? [$this->user['email']] : null,
            'ph' => isset($this->user['phone']) ? [$this->user['phone']] : null,
            'fn' => isset($this->user['first_name']) ? [$this->user['first_name']] : null,
            'ln' => isset($this->user['last_name']) ? [$this->user['last_name']] : null,
            'ct' => isset($this->user['city']) ? [$this->user['city']] : null,
            'country' => isset($this->user['country']) ? [$this->user['country']] : null,
            'external_id' => isset($this->user['external_id']) ? [$this->user['external_id']] : null,
        ]);

        $customData = array_filter([
            'currency' => $this->params['currency'] ?? null,
            'value' => $this->params['value'] ?? null,
            'order_id' => $this->params['transaction_id'] ?? null,
            'search_string' => $this->params['search_term'] ?? null,
            'content_type' => $items ? 'product' : null,
            'content_ids' => $items ? array_column($items, 'item_id') : null,
            'contents' => $items ? array_map(fn (array $item): array => [
                'id' => $item['item_id'],
                'quantity' => $item['quantity'] ?? 1,
                'item_price' => $item['price'] ?? null,
            ], $items) : null,
            'num_items' => $items ? array_sum(array_column($items, 'quantity')) : null,
        ], fn ($value) => $value !== null);

        $payload = array_filter([
            'data' => [[
                'event_name' => $eventName,
                'event_time' => $this->timestamp,
                'event_id' => $this->eventId,
                'action_source' => 'website',
                'event_source_url' => $this->context['url'],
                'user_data' => $userData,
                'custom_data' => (object) $customData,
            ]],
            'test_event_code' => filled($px['fb_test_code'] ?? null) ? $px['fb_test_code'] : null,
        ]);

        $version = config('services.meta.graph_version');

        Http::timeout(10)
            ->post("https://graph.facebook.com/{$version}/{$px['fb_pixel']}/events?access_token=".urlencode((string) $px['fb_token']), $payload)
            ->throw();
    }

    /**
     * @param  array<string, mixed>  $px
     */
    private function sendToTikTok(array $px, string $eventName): void
    {
        $items = $this->params['items'] ?? [];

        $payload = array_filter([
            'event_source' => 'web',
            'event_source_id' => $px['tiktok'],
            'test_event_code' => filled($px['tiktok_test_code'] ?? null) ? $px['tiktok_test_code'] : null,
            'data' => [[
                'event' => $eventName,
                'event_time' => $this->timestamp,
                'event_id' => $this->eventId,
                'user' => array_filter([
                    'ip' => $this->context['ip'],
                    'user_agent' => $this->context['user_agent'],
                    'ttp' => $this->context['ttp'],
                    'ttclid' => $this->context['ttclid'],
                    'email' => $this->user['email'] ?? null,
                    'phone' => $this->user['phone'] ?? null,
                    'external_id' => $this->user['external_id'] ?? null,
                ]),
                'page' => ['url' => $this->context['url']],
                'properties' => (object) array_filter([
                    'currency' => $this->params['currency'] ?? null,
                    'value' => $this->params['value'] ?? null,
                    'order_id' => $this->params['transaction_id'] ?? null,
                    'query' => $this->params['search_term'] ?? null,
                    'content_type' => $items ? 'product' : null,
                    'contents' => $items ? array_map(fn (array $item): array => [
                        'content_id' => $item['item_id'],
                        'content_name' => $item['item_name'] ?? null,
                        'quantity' => $item['quantity'] ?? 1,
                        'price' => $item['price'] ?? null,
                    ], $items) : null,
                ], fn ($value) => $value !== null),
            ]],
        ]);

        $response = Http::timeout(10)
            ->withHeaders(['Access-Token' => (string) $px['tiktok_token']])
            ->post('https://business-api.tiktok.com/open_api/v1.3/event/track/', $payload)
            ->throw();

        // TikTok answers HTTP 200 with a non-zero `code` on failure.
        if ((int) $response->json('code', 0) !== 0) {
            throw new \RuntimeException('TikTok Events API: '.$response->json('message'));
        }
    }

    /**
     * @param  array<string, mixed>  $px
     */
    private function sendToGa4(array $px): void
    {
        $query = http_build_query(['measurement_id' => $px['ga4'], 'api_secret' => $px['ga4_api_secret']]);

        Http::timeout(10)
            ->post('https://www.google-analytics.com/mp/collect?'.$query, [
                'client_id' => $this->context['ga_client_id'],
                'timestamp_micros' => $this->timestamp * 1_000_000,
                'events' => [['name' => $this->name, 'params' => $this->params]],
            ])
            ->throw();
    }

    private function attempt(string $platform, callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            Log::warning("Server-side {$platform} event failed", [
                'event' => $this->name,
                'event_id' => $this->eventId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
