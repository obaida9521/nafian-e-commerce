<?php

namespace App\Http\Controllers\Storefront;

use App\Exceptions\CouponException;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AnalyticsService;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly AnalyticsService $analytics,
    ) {}

    /**
     * Build the AJAX payload: re-rendered drawer, live count, and a toast.
     */
    private function cartJson(string $message, string $type = 'success', bool $open = true): JsonResponse
    {
        return response()->json([
            'ok' => $type !== 'error',
            'html' => view('storefront.partials.cart-contents')->render(),
            'page_html' => request()->boolean('bag_page')
                ? view('storefront.partials.bag-page', ['items' => $this->cart->getItems(), 'summary' => $this->cart->getSummary()])->render()
                : null,
            'count' => $this->cart->getCount(),
            'message' => $message,
            'type' => $type,
            'open' => $open,
            'analytics' => $this->analytics->pull(),
        ]);
    }

    public function index(): View
    {
        $items = $this->cart->getItems();
        $summary = $this->cart->getSummary();

        if ($items->isNotEmpty()) {
            $this->analytics->viewCart($items, $summary);
        }

        return view('storefront.cart', [
            'items' => $items,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'color' => ['nullable', 'string'],
            'size' => ['nullable', 'string'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'buy_now' => ['nullable', 'boolean'],
        ]);

        $quantity = (int) ($data['quantity'] ?? 1);
        $variant = $this->resolveVariant($data);

        if (! $variant) {
            $message = 'একটি অপশন বেছে নিন।';

            return $request->wantsJson()
                ? $this->cartJson($message, 'error', false)
                : back()->with('error', $message);
        }

        if ($variant->available_quantity < 1) {
            $message = 'দুঃখিত, পণ্যটি স্টকে নেই।';

            return $request->wantsJson()
                ? $this->cartJson($message, 'error', false)
                : back()->with('error', $message);
        }

        $this->cart->add($variant->id, $quantity);
        $this->analytics->addToCart($variant, $quantity);
        $message = $variant->product->name.' ব্যাগে যোগ হয়েছে।';

        if ($request->boolean('buy_now')) {
            return $request->wantsJson()
                ? response()->json(['redirect' => route('store.checkout')])
                : redirect()->route('store.checkout');
        }

        return $request->wantsJson()
            ? $this->cartJson($message)
            : back()->with('success', $message)->with('open_cart', true);
    }

    public function update(Request $request, ProductVariant $variant): RedirectResponse|JsonResponse
    {
        $quantity = (int) $request->integer('quantity');
        $this->cart->update($variant->id, $quantity);

        return $request->wantsJson()
            ? $this->cartJson('ব্যাগ আপডেট হয়েছে।', open: false)
            : back();
    }

    public function destroy(Request $request, ProductVariant $variant): RedirectResponse|JsonResponse
    {
        $line = $this->cart->getItems()->first(fn (array $line) => $line['variant']->id === $variant->id);
        $this->cart->remove($variant->id);

        if ($line) {
            $this->analytics->removeFromCart($line['variant'], $line['quantity']);
        }

        return $request->wantsJson()
            ? $this->cartJson('পণ্যটি সরানো হয়েছে।', open: false)
            : back();
    }

    public function applyCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $code = $request->validate(['code' => ['required', 'string', 'max:50']])['code'];

        try {
            $this->cart->applyCoupon($code);
        } catch (CouponException $e) {
            return $request->wantsJson()
                ? $this->cartJson($e->getMessage(), 'error', false)
                : back()->with('error', $e->getMessage());
        }

        $message = "কুপন {$code} প্রয়োগ হয়েছে।";

        return $request->wantsJson()
            ? $this->cartJson($message, open: false)
            : back()->with('success', $message);
    }

    public function removeCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $this->cart->removeCoupon();

        return $request->wantsJson()
            ? $this->cartJson('কুপন সরানো হয়েছে।', open: false)
            : back();
    }

    /**
     * @param  array{product_id?: ?int, variant_id?: ?int, color?: ?string, size?: ?string}  $data
     */
    private function resolveVariant(array $data): ?ProductVariant
    {
        if (! empty($data['variant_id'])) {
            return ProductVariant::with('product')->find($data['variant_id']);
        }

        if (empty($data['product_id'])) {
            return null;
        }

        /** @var Product|null $product */
        $product = Product::with('variants.attributeValues.attribute')->find($data['product_id']);

        if (! $product) {
            return null;
        }

        $hasSelection = ! empty($data['color']) || ! empty($data['size']);

        if ($hasSelection) {
            return $product->findVariant($data['color'] ?? null, $data['size'] ?? null);
        }

        // No selection: only auto-pick when the product is single-variant.
        // Multi-variant products must be chosen on the card picker or PDP.
        if ($product->variants->count() === 1) {
            return $product->variants->first();
        }

        return null;
    }
}
