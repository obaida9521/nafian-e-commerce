<?php

namespace App\Http\Controllers\Storefront;

use App\Exceptions\CouponException;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    /**
     * Build the AJAX payload: re-rendered drawer, live count, and a toast.
     */
    private function cartJson(string $message, string $type = 'success', bool $open = true): JsonResponse
    {
        return response()->json([
            'ok' => $type !== 'error',
            'html' => view('storefront.partials.cart-contents')->render(),
            'count' => $this->cart->getCount(),
            'message' => $message,
            'type' => $type,
            'open' => $open,
        ]);
    }

    public function index(): RedirectResponse
    {
        // The bag is a drawer in the storefront; deep-link opens it on the home page.
        return redirect()->route('store.home')->with('open_cart', true);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'color' => ['nullable', 'string'],
            'size' => ['nullable', 'string'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $quantity = (int) ($data['quantity'] ?? 1);
        $variant = $this->resolveVariant($data);

        if (! $variant) {
            $message = 'Please choose an available colour and size.';

            return $request->wantsJson()
                ? $this->cartJson($message, 'error', false)
                : back()->with('error', $message);
        }

        if ($variant->available_quantity < 1) {
            $message = 'Sorry, that item is out of stock.';

            return $request->wantsJson()
                ? $this->cartJson($message, 'error', false)
                : back()->with('error', $message);
        }

        $this->cart->add($variant->id, $quantity);
        $message = $variant->product->name.' added to your bag.';

        return $request->wantsJson()
            ? $this->cartJson($message)
            : back()->with('success', $message)->with('open_cart', true);
    }

    public function update(Request $request, ProductVariant $variant): RedirectResponse|JsonResponse
    {
        $quantity = (int) $request->integer('quantity');
        $this->cart->update($variant->id, $quantity);

        return $request->wantsJson()
            ? $this->cartJson('Bag updated.')
            : back()->with('open_cart', true);
    }

    public function destroy(Request $request, ProductVariant $variant): RedirectResponse|JsonResponse
    {
        $this->cart->remove($variant->id);

        return $request->wantsJson()
            ? $this->cartJson('Item removed.')
            : back()->with('open_cart', true);
    }

    public function applyCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $code = $request->validate(['code' => ['required', 'string', 'max:50']])['code'];

        try {
            $this->cart->applyCoupon($code);
        } catch (CouponException $e) {
            return $request->wantsJson()
                ? $this->cartJson($e->getMessage(), 'error')
                : back()->with('error', $e->getMessage())->with('open_cart', true);
        }

        $message = "Coupon {$code} applied.";

        return $request->wantsJson()
            ? $this->cartJson($message)
            : back()->with('success', $message)->with('open_cart', true);
    }

    public function removeCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $this->cart->removeCoupon();

        return $request->wantsJson()
            ? $this->cartJson('Coupon removed.')
            : back()->with('open_cart', true);
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
