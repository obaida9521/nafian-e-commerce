<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): View
    {
        $ordersList = Order::with('user')
            ->withCount('items')
            ->when($request->string('search')->toString(), function ($query, string $search) {
                $query->where('order_number', 'like', "%{$search}%")
                    ->orWhere('shipping_name', 'like', "%{$search}%")
                    ->orWhere('guest_phone', 'like', "%{$search}%");
            })
            ->when($request->filled('status'), fn ($q) => $q->byStatus($request->string('status')))
            ->latest()
            ->paginate(config('shop.per_page'))
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $ordersList,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load('items.variant', 'payments', 'user', 'coupon');

        $next = $order->status->nextStatuses();

        return view('admin.orders.show', [
            'order' => $order,
            'flow' => $this->fulfilmentFlow($order),
            'primaryAction' => collect($next)->first(fn (OrderStatus $s) => $s !== OrderStatus::Cancelled),
            'cancellable' => in_array(OrderStatus::Cancelled, $next, true),
            'timeline' => $this->timeline($order),
        ]);
    }

    /**
     * The 5-stage fulfilment flow with each stage's state relative to the order.
     *
     * @return list<array{label: string, state: string}>
     */
    private function fulfilmentFlow(Order $order): array
    {
        $stages = [
            OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing,
            OrderStatus::Shipped, OrderStatus::Delivered,
        ];

        $halted = in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Refunded], true);
        $currentIndex = array_search($order->status, $stages, true);
        if ($currentIndex === false) {
            $currentIndex = OrderStatus::Refunded === $order->status ? count($stages) : -1;
        }

        return collect($stages)->map(fn (OrderStatus $s, int $i) => [
            'label' => $s->label(),
            'state' => $halted ? ($i < $currentIndex ? 'done' : 'halted')
                : ($i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : 'upcoming')),
        ])->all();
    }

    /**
     * @return list<array{label: string, at: ?string, done: bool}>
     */
    private function timeline(Order $order): array
    {
        return collect([
            ['label' => 'Order placed', 'at' => $order->created_at, 'done' => true],
            ['label' => 'Shipped', 'at' => $order->shipped_at, 'done' => $order->shipped_at !== null],
            ['label' => 'Delivered', 'at' => $order->delivered_at, 'done' => $order->delivered_at !== null],
            ['label' => 'Cancelled', 'at' => $order->cancelled_at, 'done' => $order->cancelled_at !== null],
        ])->filter(fn ($t) => $t['done'] || $t['label'] !== 'Cancelled')
            ->map(fn ($t) => [
                'label' => $t['label'],
                'at' => $t['at']?->format('M j, Y · g:i A'),
                'done' => $t['done'],
            ])->values()->all();
    }

    public function updateStatus(StoreOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $this->orders->updateStatus(
            $order,
            OrderStatus::from($request->string('status')->toString()),
            auth('admin')->user(),
        );

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order status updated.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'cancelled_reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->orders->cancelOrder($order, $validated['cancelled_reason'], auth('admin')->user());

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order cancelled.');
    }
}
