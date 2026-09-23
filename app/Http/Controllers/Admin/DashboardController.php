<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Recent-orders filter tabs → the statuses each one covers.
     *
     * @var array<string, list<string>>
     */
    private const ORDER_TABS = [
        'all' => [],
        'new' => ['pending', 'confirmed'],
        'processing' => ['processing'],
        'shipped' => ['shipped'],
    ];

    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'days' => ['nullable', Rule::in([7, 30, 90])],
            'tab' => ['nullable', Rule::in(array_keys(self::ORDER_TABS))],
        ]);

        $days = (int) ($validated['days'] ?? 30);
        $tab = $validated['tab'] ?? 'all';

        return view('admin.dashboard.index', [
            'days' => $days,
            'tab' => $tab,
            'overview' => $this->reports->getDashboardOverview($days),
            'recentOrders' => Order::query()
                ->when(self::ORDER_TABS[$tab] !== [], fn ($q) => $q->whereIn('status', self::ORDER_TABS[$tab]))
                ->latest()
                ->limit(5)
                ->get(),
            'products' => Product::query()
                ->with(['variants.attributeValues.attribute', 'categories', 'media'])
                ->latest('updated_at')
                ->limit(3)
                ->get(),
            'productCount' => Product::count(),
            'variantCount' => ProductVariant::count(),
            'newOrderCount' => Order::whereIn('status', [OrderStatus::Pending->value])->count(),
        ]);
    }
}
