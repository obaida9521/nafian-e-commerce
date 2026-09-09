<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(): View
    {
        $monthStart = Carbon::today()->startOfMonth();

        return view('admin.dashboard.index', [
            'stats' => $this->reports->getDashboardStats(),
            'salesChart' => $this->reports->getSalesChart('7d'),
            'statusBreakdown' => $this->reports->getOrderStatusBreakdown(),
            'topProducts' => $this->reports->getTopProducts(),
            'monthlyPnl' => $this->reports->getMonthlyProfitLoss(12),
            'monthPnl' => $this->reports->getProfitAndLoss($monthStart, Carbon::today()),
            'monthLabel' => $monthStart->format('F Y'),
        ]);
    }
}
