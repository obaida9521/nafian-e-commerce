<?php

namespace App\Http\Controllers\Storefront;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CheckoutRequest;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly OrderService $orders,
    ) {}

    public function index(): View|RedirectResponse
    {
        $items = $this->cart->getItems();

        if ($items->isEmpty()) {
            return redirect()->route('store.shop')->with('error', 'Your bag is empty.');
        }

        return view('storefront.checkout', [
            'items' => $items,
            'summary' => $this->cart->getSummary(),
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $items = $this->cart->getItems();

        if ($items->isEmpty()) {
            return redirect()->route('store.shop')->with('error', 'Your bag is empty.');
        }

        $data = $request->validated();
        $summary = $this->cart->getSummary();

        $orderData = [
            'items' => $items->map(fn ($line) => [
                'variant_id' => $line['variant']->id,
                'quantity' => $line['quantity'],
            ])->all(),
            'shipping_name' => trim($data['first_name'].' '.$data['last_name']),
            'shipping_phone' => $data['phone'],
            'shipping_address' => trim($data['address'].(! empty($data['apt']) ? ', '.$data['apt'] : '')),
            'shipping_city' => $data['city'],
            'shipping_district' => $data['zip'],
            'payment_method' => $data['payment_method'],
            'coupon_code' => $summary['coupon'],
            'delivery_charge' => $summary['delivery'],
            'guest_email' => auth('web')->check() ? auth('web')->user()->email : $data['email'],
            'guest_phone' => $data['phone'],
        ];

        try {
            $order = $this->orders->createOrder($orderData, auth('web')->user());
        } catch (InsufficientStockException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->cart->clear();

        return redirect()
            ->route('store.order.confirmation', $order->order_number)
            ->with('order_email', $data['email'] ?? $order->guest_email);
    }
}
