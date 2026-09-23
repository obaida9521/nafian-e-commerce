<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Enums\OrderStatus;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Statuses that represent realised revenue.
     *
     * @var array<int, string>
     */
    private const REVENUE_STATUSES = ['confirmed', 'processing', 'shipped', 'delivered'];

    /**
     * @return array{today_orders: int, today_revenue: float, total_products: int, low_stock_count: int}
     */
    public function getDashboardStats(): array
    {
        $today = Carbon::today();

        return [
            'today_orders' => Order::whereDate('created_at', $today)->count(),
            'today_revenue' => (float) Order::whereDate('created_at', $today)
                ->whereIn('status', self::REVENUE_STATUSES)
                ->sum('total_amount'),
            'total_products' => Product::count(),
            'low_stock_count' => $this->lowStockQuery()->count(),
        ];
    }

    /**
     * @return array{labels: array<int, string>, revenue: array<int, float>, orders: array<int, int>}
     */
    public function getSalesChart(string $period = '7d'): array
    {
        $days = match ($period) {
            '30d' => 30,
            '14d' => 14,
            default => 7,
        };

        $start = Carbon::today()->subDays($days - 1);

        $rows = Order::whereDate('created_at', '>=', $start)
            ->whereIn('status', self::REVENUE_STATUSES)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as orders, SUM(total_amount) as revenue')
            ->groupBy('day')
            ->pluck('revenue', 'day');

        $orderRows = Order::whereDate('created_at', '>=', $start)
            ->whereIn('status', self::REVENUE_STATUSES)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as orders')
            ->groupBy('day')
            ->pluck('orders', 'day');

        $labels = [];
        $revenue = [];
        $orders = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->toDateString();
            $labels[] = $date->format('M j');
            $revenue[] = (float) ($rows[$key] ?? 0);
            $orders[] = (int) ($orderRows[$key] ?? 0);
        }

        return compact('labels', 'revenue', 'orders');
    }

    /**
     * @return array<string, int>
     */
    public function getOrderStatusBreakdown(): array
    {
        $counts = Order::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $result = [];

        foreach (OrderStatus::cases() as $status) {
            $result[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $result;
    }

    /**
     * @return Collection<int, object>
     */
    public function getTopProducts(int $limit = 10): Collection
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', self::REVENUE_STATUSES)
            ->selectRaw('order_items.product_name, SUM(order_items.quantity) as units_sold, SUM(order_items.line_total) as revenue')
            ->groupBy('order_items.product_name')
            ->orderByDesc('units_sold')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{gross_revenue: float, discount_total: float, delivery_total: float, net_revenue: float, cod_total: float, online_total: float, order_count: int}
     */
    public function getRevenueReport(Carbon $from, Carbon $to): array
    {
        $base = Order::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereIn('status', self::REVENUE_STATUSES);

        return [
            'gross_revenue' => (float) (clone $base)->sum('subtotal'),
            'discount_total' => (float) (clone $base)->sum('discount_amount'),
            'delivery_total' => (float) (clone $base)->sum('delivery_charge'),
            'net_revenue' => (float) (clone $base)->sum('total_amount'),
            'cod_total' => (float) (clone $base)->where('payment_method', 'cod')->sum('total_amount'),
            'online_total' => (float) (clone $base)->where('payment_method', '!=', 'cod')->sum('total_amount'),
            'order_count' => (clone $base)->count(),
        ];
    }

    /**
     * Profit & loss across both online orders and POS sales for a date range.
     *
     * COGS uses the per-line cost snapshot for POS sales; online orders fall
     * back to the variant's current cost_price (no historical snapshot exists).
     *
     * @return array{
     *     order_revenue: float, order_cogs: float, pos_revenue: float, pos_cogs: float,
     *     total_revenue: float, total_cogs: float, gross_profit: float,
     *     expenses_total: float, net_profit: float,
     *     expenses_by_category: array<string, float>
     * }
     */
    public function getProfitAndLoss(Carbon $from, Carbon $to): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        $orderRevenue = (float) Order::whereBetween('created_at', [$start, $end])
            ->whereIn('status', self::REVENUE_STATUSES)
            ->sum('total_amount');

        $orderCogs = (float) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.variant_id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->whereIn('orders.status', self::REVENUE_STATUSES)
            ->sum(DB::raw('COALESCE(product_variants.cost_price, 0) * order_items.quantity'));

        $posRevenue = (float) Sale::whereBetween('created_at', [$start, $end])->sum('total_amount');

        $posCogs = (float) DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->sum(DB::raw('sale_items.cost_price * sale_items.quantity'));

        $expensesByCategory = [];
        foreach (ExpenseCategory::cases() as $category) {
            $expensesByCategory[$category->value] = 0.0;
        }

        Expense::between($from, $to)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get()
            ->each(function ($row) use (&$expensesByCategory): void {
                $key = $row->category instanceof ExpenseCategory ? $row->category->value : (string) $row->category;
                $expensesByCategory[$key] = (float) $row->total;
            });

        $expensesTotal = array_sum($expensesByCategory);

        $totalRevenue = round($orderRevenue + $posRevenue, 2);
        $totalCogs = round($orderCogs + $posCogs, 2);
        $grossProfit = round($totalRevenue - $totalCogs, 2);

        return [
            'order_revenue' => round($orderRevenue, 2),
            'order_cogs' => round($orderCogs, 2),
            'pos_revenue' => round($posRevenue, 2),
            'pos_cogs' => round($posCogs, 2),
            'total_revenue' => $totalRevenue,
            'total_cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'expenses_total' => round($expensesTotal, 2),
            'net_profit' => round($grossProfit - $expensesTotal, 2),
            'expenses_by_category' => $expensesByCategory,
        ];
    }

    /**
     * Month-by-month profit & loss for the trailing window (oldest → newest).
     *
     * @return array{
     *     labels: array<int, string>, revenue: array<int, float>,
     *     cogs: array<int, float>, expenses: array<int, float>, profit: array<int, float>
     * }
     */
    public function getMonthlyProfitLoss(int $months = 12): array
    {
        $labels = $revenue = $cogs = $expenses = $profit = [];
        $start = Carbon::today()->startOfMonth()->subMonths($months - 1);

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $start->copy()->addMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();
            $pl = $this->getProfitAndLoss($monthStart, $monthEnd);

            $labels[] = $monthStart->format('M y');
            $revenue[] = $pl['total_revenue'];
            $cogs[] = $pl['total_cogs'];
            $expenses[] = $pl['expenses_total'];
            $profit[] = $pl['net_profit'];
        }

        return compact('labels', 'revenue', 'cogs', 'expenses', 'profit');
    }

    /**
     * Admin dashboard: KPIs vs the previous period, daily sales, status mix and stock alerts.
     *
     * @return array{
     *     revenue: float, revenue_change: ?float, orders: int, orders_change: ?float,
     *     average_order: float, average_order_change: ?float,
     *     cod_success_rate: ?int, returned: int,
     *     daily: list<array{date: Carbon, total: float}>,
     *     statuses: array{new: int, processing: int, shipped: int, delivered: int, cancelled: int},
     *     low_stock: Collection<int, ProductVariant>
     * }
     */
    public function getDashboardOverview(int $days = 30, int $chartDays = 14): array
    {
        $end = Carbon::now();
        $start = Carbon::today()->subDays($days - 1);
        $previousStart = $start->copy()->subDays($days);

        $current = $this->periodTotals($start, $end);
        $previous = $this->periodTotals($previousStart, $start->copy()->subSecond());

        $codClosed = Order::whereBetween('created_at', [$start, $end])
            ->where('payment_method', 'cod')
            ->whereIn('status', ['delivered', 'cancelled', 'refunded'])
            ->selectRaw("SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered, COUNT(*) as closed")
            ->first();

        $statusCounts = Order::whereBetween('created_at', [$start, $end])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $chartStart = Carbon::today()->subDays($chartDays - 1);
        $dailyRows = Order::where('created_at', '>=', $chartStart)
            ->whereIn('status', self::REVENUE_STATUSES)
            ->selectRaw('DATE(created_at) as day, SUM(total_amount) as revenue')
            ->groupBy('day')
            ->pluck('revenue', 'day');

        $daily = [];
        for ($i = 0; $i < $chartDays; $i++) {
            $date = $chartStart->copy()->addDays($i);
            $daily[] = ['date' => $date, 'total' => (float) ($dailyRows[$date->toDateString()] ?? 0)];
        }

        $count = fn (string ...$statuses): int => (int) collect($statuses)->sum(fn ($s) => $statusCounts[$s] ?? 0);

        return [
            'revenue' => $current['revenue'],
            'revenue_change' => $this->percentChange($current['revenue'], $previous['revenue']),
            'orders' => $current['orders'],
            'orders_change' => $this->percentChange($current['orders'], $previous['orders']),
            'average_order' => $current['average'],
            'average_order_change' => $this->percentChange($current['average'], $previous['average']),
            'cod_success_rate' => $codClosed && $codClosed->closed > 0 ? (int) round($codClosed->delivered / $codClosed->closed * 100) : null,
            'returned' => $count('cancelled', 'refunded'),
            'daily' => $daily,
            'statuses' => [
                'new' => $count('pending', 'confirmed'),
                'processing' => $count('processing'),
                'shipped' => $count('shipped'),
                'delivered' => $count('delivered'),
                'cancelled' => $count('cancelled', 'refunded'),
            ],
            'low_stock' => $this->lowStockQuery()
                ->with('product', 'attributeValues')
                ->orderByRaw('(stock_quantity - reserved_quantity)')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * @return array{revenue: float, orders: int, average: float}
     */
    private function periodTotals(Carbon $from, Carbon $to): array
    {
        $row = Order::whereBetween('created_at', [$from, $to])
            ->whereIn('status', self::REVENUE_STATUSES)
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue')
            ->first();

        $orders = (int) ($row->orders ?? 0);
        $revenue = (float) ($row->revenue ?? 0);

        return ['revenue' => $revenue, 'orders' => $orders, 'average' => $orders > 0 ? round($revenue / $orders, 2) : 0.0];
    }

    private function percentChange(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    /**
     * @return Builder<ProductVariant>
     */
    private function lowStockQuery()
    {
        $threshold = (int) config('shop.low_stock_threshold', 5);

        return ProductVariant::where('is_active', true)
            ->whereRaw('(stock_quantity - reserved_quantity) <= ?', [$threshold]);
    }
}
