<?php

namespace App\Services;

use App\Jobs\SendServerAnalyticsEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Storefront e-commerce tracking.
 *
 * Events are named and shaped the GA4 way (`add_to_cart`, `{currency, value, items}`).
 * Browser events are queued in the session and flushed by `storefront.partials.pixels`
 * on the next rendered page (or returned in AJAX payloads), where `nfTrack()` fans them
 * out to Meta, GA4/GTM and TikTok. Key events are also sent server-side (Meta Conversions
 * API, TikTok Events API, GA4 Measurement Protocol) with the same event id so the
 * platforms deduplicate them.
 */
class AnalyticsService
{
    private const SESSION_KEY = 'analytics.events';

    /**
     * GA4 event name => platform event names. Events missing a platform stay GA4/GTM only.
     *
     * @var array<string, array{meta?: string, tiktok?: string}>
     */
    public const EVENT_MAP = [
        'view_item' => ['meta' => 'ViewContent', 'tiktok' => 'ViewContent'],
        'view_item_list' => [],
        'view_cart' => [],
        'search' => ['meta' => 'Search', 'tiktok' => 'Search'],
        'add_to_cart' => ['meta' => 'AddToCart', 'tiktok' => 'AddToCart'],
        'remove_from_cart' => [],
        'begin_checkout' => ['meta' => 'InitiateCheckout', 'tiktok' => 'InitiateCheckout'],
        'purchase' => ['meta' => 'Purchase', 'tiktok' => 'CompletePayment'],
        'sign_up' => ['meta' => 'CompleteRegistration', 'tiktok' => 'CompleteRegistration'],
        'generate_lead' => ['meta' => 'Lead', 'tiktok' => 'SubmitForm'],
    ];

    /**
     * Events GA4 receives server-side too (it dedupes purchases by transaction id).
     *
     * @var list<string>
     */
    private const GA4_SERVER_EVENTS = ['purchase'];

    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Record an event for the browser pixels and, where configured, the server-side APIs.
     *
     * @param  array<string, mixed>  $params  GA4-style parameters
     * @param  array{email?: ?string, phone?: ?string, first_name?: ?string, last_name?: ?string, city?: ?string, external_id?: string|int|null}  $user
     */
    public function track(string $name, array $params = [], array $user = [], ?string $eventId = null): ?string
    {
        if (! $this->browserEnabled() && ! $this->serverEnabled()) {
            return null;
        }

        $eventId ??= $name.'.'.Str::uuid()->toString();
        $params = ['currency' => config('shop.currency')] + $params;

        if ($this->browserEnabled()) {
            session()->push(self::SESSION_KEY, ['name' => $name, 'params' => $params, 'id' => $eventId]);
        }

        if ($this->shouldSendServerSide($name)) {
            SendServerAnalyticsEvent::dispatch($name, $params, $eventId, $this->requestContext(), $this->userData($user), now()->timestamp);
        }

        return $eventId;
    }

    /**
     * Take the queued browser events (flushed once, by the page or AJAX response that renders them).
     *
     * @return list<array{name: string, params: array<string, mixed>, id: string}>
     */
    public function pull(): array
    {
        return session()->pull(self::SESSION_KEY, []);
    }

    public function viewItem(Product $product, ?ProductVariant $variant = null): void
    {
        $variant ??= $product->variants->where('is_active', true)->sortBy('price')->first();

        if (! $variant) {
            return;
        }

        $item = $this->variantItem($variant);
        $this->track('view_item', ['value' => $item['price'], 'items' => [$item]]);
    }

    /**
     * @param  iterable<Product>  $products
     */
    public function viewItemList(iterable $products, string $listName): void
    {
        $items = collect($products)
            ->map(function (Product $product, int $index) use ($listName): ?array {
                $variant = $product->variants->where('is_active', true)->sortBy('price')->first();

                return $variant ? $this->variantItem($variant) + ['index' => $index, 'item_list_name' => $listName] : null;
            })
            ->filter()
            ->values()
            ->all();

        if ($items !== []) {
            $this->track('view_item_list', ['item_list_name' => $listName, 'items' => $items]);
        }
    }

    public function search(string $term): void
    {
        $this->track('search', ['search_term' => $term]);
    }

    public function addToCart(ProductVariant $variant, int $quantity): void
    {
        $item = $this->variantItem($variant, $quantity);
        $this->track('add_to_cart', ['value' => round($item['price'] * $quantity, 2), 'items' => [$item]]);
    }

    public function removeFromCart(ProductVariant $variant, int $quantity): void
    {
        $item = $this->variantItem($variant, $quantity);
        $this->track('remove_from_cart', ['value' => round($item['price'] * $quantity, 2), 'items' => [$item]]);
    }

    /**
     * @param  Collection<int, array{variant: ProductVariant, quantity: int, line_total: float}>  $lines
     * @param  array{total: float, coupon?: ?string}  $summary
     */
    public function viewCart(Collection $lines, array $summary): void
    {
        $this->track('view_cart', ['value' => (float) $summary['total'], 'items' => $this->cartItems($lines)]);
    }

    /**
     * @param  Collection<int, array{variant: ProductVariant, quantity: int, line_total: float}>  $lines
     * @param  array{total: float, coupon?: ?string}  $summary
     */
    public function beginCheckout(Collection $lines, array $summary): void
    {
        $this->track('begin_checkout', array_filter([
            'value' => (float) $summary['total'],
            'coupon' => $summary['coupon'] ?? null,
            'items' => $this->cartItems($lines),
        ], fn ($value) => $value !== null), $this->currentUser());
    }

    public function purchase(Order $order): void
    {
        $order->loadMissing('items.variant.product.categories', 'items.variant.attributeValues.attribute');

        $items = $order->items->map(fn ($line): array => array_filter([
            'item_id' => (string) ($line->variant?->product_id ?? $line->sku),
            'item_name' => $line->product_name,
            'item_variant' => $line->variant_name,
            'item_category' => $line->variant?->product?->categories->first()?->name,
            'price' => (float) $line->unit_price,
            'quantity' => (int) $line->quantity,
        ], fn ($value) => $value !== null))->values()->all();

        $name = trim((string) $order->shipping_name);

        $this->track('purchase', array_filter([
            'transaction_id' => $order->order_number,
            'value' => (float) $order->total_amount,
            'shipping' => (float) $order->delivery_charge,
            'discount' => (float) $order->discount_amount,
            'coupon' => $order->coupon_code,
            'payment_type' => $order->payment_method?->value,
            'items' => $items,
        ], fn ($value) => $value !== null), [
            'email' => $order->guest_email ?? $order->user?->email,
            'phone' => $order->shipping_phone,
            'first_name' => Str::before($name, ' ') ?: null,
            'last_name' => str_contains($name, ' ') ? Str::afterLast($name, ' ') : null,
            'city' => $order->shipping_city,
            'external_id' => $order->user_id,
        ], 'purchase.'.$order->order_number);
    }

    public function signUp(string $email, ?string $phone, int $userId): void
    {
        $this->track('sign_up', ['method' => 'email'], ['email' => $email, 'phone' => $phone, 'external_id' => $userId]);
    }

    public function lead(string $source, ?string $contact = null): void
    {
        $user = [];

        if ($contact !== null) {
            $user = str_contains($contact, '@') ? ['email' => $contact] : ['phone' => $contact];
        }

        $this->track('generate_lead', ['lead_source' => $source], $user + $this->currentUser());
    }

    /**
     * Browser-side configuration for `nfTrack()`.
     *
     * @return array{meta: bool, ga4: bool, gtm: bool, tiktok: bool, map: array<string, array{meta?: string, tiktok?: string}>}
     */
    public function browserConfig(): array
    {
        $px = $this->settings->group('pixels');

        return [
            'meta' => ! empty($px['fb_enabled']) && filled($px['fb_pixel']),
            'ga4' => ! empty($px['ga4_enabled']) && filled($px['ga4']),
            'gtm' => ! empty($px['ga4_enabled']) && filled($px['gtm']),
            'tiktok' => ! empty($px['tiktok_enabled']) && filled($px['tiktok']),
            'map' => self::EVENT_MAP,
        ];
    }

    public function browserEnabled(): bool
    {
        $config = $this->browserConfig();

        return $config['meta'] || $config['ga4'] || $config['gtm'] || $config['tiktok'];
    }

    /**
     * Platforms with server-side credentials configured.
     *
     * @return array{meta: bool, tiktok: bool, ga4: bool}
     */
    public function serverPlatforms(): array
    {
        $px = $this->settings->group('pixels');

        return [
            'meta' => ! empty($px['fb_enabled']) && filled($px['fb_pixel']) && filled($px['fb_token']),
            'tiktok' => ! empty($px['tiktok_enabled']) && filled($px['tiktok']) && filled($px['tiktok_token'] ?? null),
            'ga4' => ! empty($px['ga4_enabled']) && filled($px['ga4']) && filled($px['ga4_api_secret'] ?? null),
        ];
    }

    public function serverEnabled(): bool
    {
        return in_array(true, $this->serverPlatforms(), true);
    }

    /**
     * GA4 item for a variant.
     *
     * @return array{item_id: string, item_name: string, item_variant?: string, item_category?: string, price: float, quantity: int}
     */
    public function variantItem(ProductVariant $variant, int $quantity = 1): array
    {
        $product = $variant->product;

        return array_filter([
            'item_id' => (string) $variant->product_id,
            'item_name' => $product?->name ?? $variant->sku,
            'item_variant' => $variant->display_name !== $variant->sku ? $variant->display_name : null,
            'item_category' => $product?->categories->first()?->name,
            'price' => (float) $variant->price,
            'quantity' => $quantity,
        ], fn ($value) => $value !== null);
    }

    /**
     * @param  Collection<int, array{variant: ProductVariant, quantity: int, line_total: float}>  $lines
     * @return list<array<string, mixed>>
     */
    private function cartItems(Collection $lines): array
    {
        return $lines->map(fn (array $line): array => $this->variantItem($line['variant'], $line['quantity']))->values()->all();
    }

    private function shouldSendServerSide(string $name): bool
    {
        $platforms = $this->serverPlatforms();
        $map = self::EVENT_MAP[$name] ?? [];

        return ($platforms['meta'] && isset($map['meta']))
            || ($platforms['tiktok'] && isset($map['tiktok']))
            || ($platforms['ga4'] && in_array($name, self::GA4_SERVER_EVENTS, true));
    }

    /**
     * Browser identifiers the ad platforms match on, captured while we still have the request.
     *
     * @return array{ip: ?string, user_agent: ?string, url: string, fbp: ?string, fbc: ?string, ttp: ?string, ttclid: ?string, ga_client_id: string}
     */
    private function requestContext(): array
    {
        $request = request();
        $fbc = $request->cookie('_fbc');

        if (! $fbc && $request->filled('fbclid')) {
            $fbc = 'fb.1.'.now()->getTimestampMs().'.'.$request->query('fbclid');
        }

        // `_ga` looks like GA1.1.1234567890.1700000000 — the client id is the last two parts.
        $gaCookie = (string) $request->cookie('_ga', '');
        $gaClientId = preg_match('/^GA\d\.\d\.(\d+\.\d+)$/', $gaCookie, $m) ? $m[1] : null;

        return [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->headers->get('referer') && $request->ajax() ? (string) $request->headers->get('referer') : $request->fullUrl(),
            'fbp' => $request->cookie('_fbp'),
            'fbc' => $fbc,
            'ttp' => $request->cookie('_ttp'),
            'ttclid' => $request->query('ttclid'),
            'ga_client_id' => $gaClientId ?? abs(crc32($request->session()->getId())).'.'.now()->timestamp,
        ];
    }

    /**
     * @return array{email?: ?string, phone?: ?string, external_id?: int}
     */
    private function currentUser(): array
    {
        $user = auth('web')->user();

        return $user ? ['email' => $user->email, 'phone' => $user->phone, 'external_id' => $user->id] : [];
    }

    /**
     * Normalised + SHA-256 hashed user data, as the ad platforms require.
     *
     * @param  array<string, mixed>  $user
     * @return array<string, string>
     */
    private function userData(array $user): array
    {
        $user += $this->currentUser();
        $hash = fn (?string $value): ?string => filled($value) ? hash('sha256', $value) : null;

        $phone = null;
        if (filled($user['phone'] ?? null)) {
            $digits = preg_replace('/\D/', '', latin_digits((string) $user['phone']));
            $phone = str_starts_with($digits, '880') ? $digits : '88'.ltrim($digits, '+');
        }

        return array_filter([
            'email' => $hash(filled($user['email'] ?? null) ? mb_strtolower(trim((string) $user['email'])) : null),
            'phone' => $hash($phone),
            'first_name' => $hash(filled($user['first_name'] ?? null) ? mb_strtolower(trim((string) $user['first_name'])) : null),
            'last_name' => $hash(filled($user['last_name'] ?? null) ? mb_strtolower(trim((string) $user['last_name'])) : null),
            'city' => $hash(filled($user['city'] ?? null) ? mb_strtolower(preg_replace('/\s+/', '', (string) $user['city'])) : null),
            'country' => $hash('bd'),
            'external_id' => $hash(filled($user['external_id'] ?? null) ? (string) $user['external_id'] : null),
        ]);
    }
}
