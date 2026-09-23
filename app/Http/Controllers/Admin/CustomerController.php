<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $tab = $request->string('tab')->toString() === 'guests' ? 'guests' : 'registered';

        $registeredCount = User::count();
        $guestCount = Order::whereNull('user_id')->whereNotNull('guest_email')->distinct('guest_email')->count('guest_email');

        $customers = null;
        $guests = null;
        $selected = null;
        $selectedGuest = null;

        if ($tab === 'guests') {
            $guests = $this->guestQuery($search)
                ->paginate(config('shop.per_page'))
                ->withQueryString();

            if ($email = $request->string('guest')->toString()) {
                $selectedGuest = $this->guestDetail($email);
            }
        } else {
            $customers = User::withCount('orders')
                ->withSum('orders as orders_total', 'total_amount')
                ->when($search, function ($query, string $search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                ->latest()
                ->paginate(config('shop.per_page'))
                ->withQueryString();

            if ($id = $request->integer('view')) {
                $selected = User::withCount('orders')
                    ->withSum('orders as orders_total', 'total_amount')
                    ->with(['orders' => fn ($q) => $q->latest()->take(6)])
                    ->find($id);
            }
        }

        return view('admin.customers.index', compact(
            'tab', 'customers', 'guests', 'selected', 'selectedGuest', 'registeredCount', 'guestCount'
        ));
    }

    /**
     * Guest "customers" aggregated from orders that have no linked user, keyed by email.
     *
     * @return Builder<Order>
     */
    private function guestQuery(string $search): Builder
    {
        return Order::query()
            ->whereNull('user_id')
            ->whereNotNull('guest_email')
            ->when($search, function ($query, string $search) {
                $query->where(function ($w) use ($search) {
                    $w->where('guest_email', 'like', "%{$search}%")
                        ->orWhere('guest_phone', 'like', "%{$search}%")
                        ->orWhere('shipping_name', 'like', "%{$search}%");
                });
            })
            ->selectRaw('guest_email,
                MAX(shipping_name) as name,
                MAX(guest_phone) as phone,
                COUNT(*) as orders_count,
                SUM(total_amount) as orders_total,
                MIN(created_at) as first_order_at,
                MAX(created_at) as last_order_at')
            ->groupBy('guest_email')
            ->orderByDesc('last_order_at');
    }

    /**
     * Build a single guest profile with full order history for the slide-over.
     *
     * @return array{email: string, name: string, phone: ?string, orders_count: int, orders_total: float, first_order_at: ?Carbon, orders: Collection<int, Order>}|null
     */
    private function guestDetail(string $email): ?array
    {
        $orders = Order::whereNull('user_id')
            ->where('guest_email', $email)
            ->latest()
            ->get();

        if ($orders->isEmpty()) {
            return null;
        }

        return [
            'email' => $email,
            'name' => $orders->first()->shipping_name,
            'phone' => $orders->first()->guest_phone,
            'orders_count' => $orders->count(),
            'orders_total' => (float) $orders->sum('total_amount'),
            'first_order_at' => $orders->min('created_at'),
            'orders' => $orders,
        ];
    }

    public function show(User $customer): RedirectResponse
    {
        // Customer detail is shown in the slide-over drawer on the index page.
        return redirect()->route('admin.customers.index', ['view' => $customer->id]);
    }

    public function update(Request $request, User $customer): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $customer->update(['is_active' => (bool) $validated['is_active']]);

        $this->activityLogger->log(
            auth('admin')->user(),
            'customer.updated',
            'user',
            $customer->id,
            ($validated['is_active'] ? 'Activated' : 'Deactivated')." customer {$customer->name}",
        );

        return redirect()->route('admin.customers.index', ['view' => $customer->id])->with('success', 'গ্রাহকের তথ্য আপডেট হয়েছে।');
    }
}
