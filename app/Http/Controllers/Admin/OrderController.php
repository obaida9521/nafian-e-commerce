<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrderStatusRequest;
use App\Models\Order;
use App\Services\ActivityLogger;
use App\Services\OrderService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly ActivityLogger $activityLogger,
    ) {}

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
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $ordersList,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load('items.variant.product.media', 'payments', 'user', 'coupon', 'statusHistory.admin');

        $next = $order->status->nextStatuses();

        return view('admin.orders.show', [
            'order' => $order,
            'nextStatuses' => $next,
            'cancellable' => in_array(OrderStatus::Cancelled, $next, true),
            'timeline' => $this->timeline($order),
            'previousOrders' => Order::query()
                ->where('id', '!=', $order->id)
                ->where(fn ($q) => $q->where('shipping_phone', $order->shipping_phone)
                    ->when($order->user_id, fn ($q) => $q->orWhere('user_id', $order->user_id)))
                ->count(),
        ]);
    }

    public function invoice(Order $order): View
    {
        $order->load('items', 'coupon');

        return view('admin.orders.invoice', [
            'order' => $order,
            'general' => app(SettingsService::class)->group('general'),
        ]);
    }

    public function updateDetails(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'rider_name' => ['nullable', 'string', 'max:100'],
            'rider_phone' => ['nullable', 'string', 'max:20'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['rider_phone'] = filled($validated['rider_phone'] ?? null) ? normalize_phone($validated['rider_phone']) : null;

        $order->update($validated);

        $this->activityLogger->log(
            auth('admin')->user(),
            'order.details_updated',
            'order',
            $order->id,
            "Order {$order->order_number}: rider / note updated",
        );

        return redirect()->route('admin.orders.show', $order)->with('success', 'অর্ডারের তথ্য সংরক্ষণ হয়েছে।');
    }

    /**
     * Status history rows, falling back to stored timestamps for orders placed before history was kept.
     *
     * @return list<array{label: string, at: Carbon, note: ?string}>
     */
    private function timeline(Order $order): array
    {
        if ($order->statusHistory->isNotEmpty()) {
            return $order->statusHistory->map(fn ($h) => [
                'label' => $h->status->labelBn(),
                'at' => $h->created_at,
                'note' => $h->note,
            ])->all();
        }

        return collect([
            ['label' => OrderStatus::Pending->labelBn(), 'at' => $order->created_at, 'note' => 'ওয়েবসাইট'],
            ['label' => OrderStatus::Shipped->labelBn(), 'at' => $order->shipped_at, 'note' => null],
            ['label' => OrderStatus::Delivered->labelBn(), 'at' => $order->delivered_at, 'note' => null],
            ['label' => OrderStatus::Cancelled->labelBn(), 'at' => $order->cancelled_at, 'note' => $order->cancelled_reason],
        ])->filter(fn ($t) => $t['at'] !== null)->values()->all();
    }

    public function updateStatus(StoreOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $this->orders->updateStatus(
            $order,
            OrderStatus::from($request->string('status')->toString()),
            auth('admin')->user(),
        );

        return redirect()->route('admin.orders.show', $order)->with('success', 'অর্ডারের অবস্থা বদলানো হয়েছে।');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'cancelled_reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->orders->cancelOrder($order, $validated['cancelled_reason'], auth('admin')->user());

        return redirect()->route('admin.orders.show', $order)->with('success', 'অর্ডার বাতিল করা হয়েছে।');
    }
}
