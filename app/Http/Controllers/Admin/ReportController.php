<?php

namespace App\Http\Controllers\Admin;

use App\Exports\RevenueOrdersExport;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $report = $this->reports->getRevenueReport($from, $to);

        return view('admin.reports.index', [
            'from' => $from,
            'to' => $to,
            'activeRange' => $request->string('range')->toString() ?: 'custom',
            'report' => $report,
            'profitLoss' => $this->reports->getProfitAndLoss($from, $to),
            'topProducts' => $this->reports->getTopProducts(),
            'salesChart' => $this->reports->getSalesChart('30d'),
            'paymentSplit' => [
                'labels' => ['Online', 'Cash on delivery', 'Delivery'],
                'values' => [
                    round((float) $report['online_total'], 2),
                    round((float) $report['cod_total'], 2),
                    round((float) $report['delivery_total'], 2),
                ],
            ],
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->range($request);

        return (new RevenueOrdersExport($from, $to))
            ->download('orders-'.$from->toDateString().'-to-'.$to->toDateString().'.xlsx');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $to = Carbon::today();

        $from = match ($request->string('range')->toString()) {
            'today' => Carbon::today(),
            '7d' => Carbon::today()->subDays(6),
            '30d' => Carbon::today()->subDays(29),
            default => null,
        };

        if ($from === null) {
            $from = $request->date('from') ? Carbon::parse($request->date('from')) : Carbon::today()->subDays(29);
            $to = $request->date('to') ? Carbon::parse($request->date('to')) : Carbon::today();
        }

        return [$from, $to];
    }
}
