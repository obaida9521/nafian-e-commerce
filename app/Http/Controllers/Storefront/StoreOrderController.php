<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreOrderController extends Controller
{
    public function confirmation(Order $order): View
    {
        $order->load('items', 'payments');

        return view('storefront.confirmation', [
            'order' => $order,
            'email' => session('order_email', $order->guest_email),
        ]);
    }

    public function trackForm(Request $request): View
    {
        return view('storefront.track', [
            'order' => null,
            'prefillOrder' => $request->string('order')->toString(),
        ]);
    }

    public function track(Request $request): View
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150'],
        ]);

        $order = Order::with('items', 'user')
            ->where('order_number', $data['order_number'])
            ->where(function ($q) use ($data): void {
                $q->where('guest_email', $data['email'])
                    ->orWhereHas('user', fn ($u) => $u->where('email', $data['email']));
            })
            ->first();

        return view('storefront.track', [
            'order' => $order,
            'notFound' => $order === null,
            'prefillOrder' => $data['order_number'],
        ]);
    }
}
