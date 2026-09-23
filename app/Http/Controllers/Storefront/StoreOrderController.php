<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StoreOrderController extends Controller
{
    private const RECENT_ORDERS_KEY = 'recent_orders';

    public function __construct(
        private readonly OrderService $orders,
        private readonly SettingsService $settings,
    ) {}

    public function confirmation(Order $order): View|RedirectResponse
    {
        if (! $this->canView($order)) {
            return redirect()->route('store.track', ['order' => $order->order_number]);
        }

        $order->load('items.variant.product.media', 'items.variant.product.categories', 'statusHistory');

        return view('storefront.confirmation', [
            'order' => $order,
            'general' => $this->settings->group('general'),
        ]);
    }

    public function trackForm(Request $request): View
    {
        return view('storefront.track', [
            'order' => null,
            'notFound' => false,
            'prefillOrder' => $request->string('order')->toString(),
            'prefillPhone' => '',
            'general' => $this->settings->group('general'),
        ]);
    }

    public function track(Request $request): View
    {
        $request->merge([
            'order_number' => strtoupper(latin_digits(trim((string) $request->input('order_number')))),
            'phone' => normalize_phone((string) $request->input('phone')),
        ]);

        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:30'],
            'phone' => ['required', 'string', 'max:20'],
        ], [], ['order_number' => 'অর্ডার নম্বর', 'phone' => 'মোবাইল নম্বর']);

        $order = $this->findOrder($data['order_number'], $data['phone']);

        if ($order) {
            session()->push(self::RECENT_ORDERS_KEY, $order->order_number);
            $order->load('items.variant.product.media', 'items.variant.product.categories', 'statusHistory');
        }

        return view('storefront.track', [
            'order' => $order,
            'notFound' => $order === null,
            'prefillOrder' => $data['order_number'],
            'prefillPhone' => $data['phone'],
            'general' => $this->settings->group('general'),
        ]);
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($this->canView($order), 403);

        try {
            $this->orders->cancelByCustomer($order);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', "অর্ডার {$order->order_number} বাতিল করা হয়েছে।");
    }

    private function findOrder(string $orderNumber, string $phone): ?Order
    {
        return Order::query()
            ->where('order_number', $orderNumber)
            ->where(fn ($q) => $q->where('shipping_phone', $phone)->orWhere('guest_phone', $phone))
            ->first();
    }

    /**
     * The shopper placed or already looked up this order in this session, or owns it.
     */
    private function canView(Order $order): bool
    {
        if ($order->user_id !== null && $order->user_id === auth('web')->id()) {
            return true;
        }

        return in_array($order->order_number, (array) session(self::RECENT_ORDERS_KEY, []), true);
    }
}
