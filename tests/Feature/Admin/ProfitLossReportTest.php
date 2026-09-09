<?php

namespace Tests\Feature\Admin;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitLossReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_net_profit_subtracts_cogs_and_expenses(): void
    {
        $today = Carbon::today();

        $sale = Sale::factory()->create([
            'subtotal' => 500, 'discount_amount' => 0, 'total_amount' => 500,
            'amount_paid' => 500, 'change_due' => 0, 'created_at' => $today,
        ]);
        SaleItem::factory()->create([
            'sale_id' => $sale->id, 'unit_price' => 250, 'cost_price' => 150,
            'quantity' => 2, 'line_total' => 500,
        ]);

        Expense::factory()->create(['amount' => 100, 'spent_on' => $today->toDateString()]);

        $pl = app(ReportService::class)->getProfitAndLoss($today->copy(), $today->copy());

        $this->assertSame(500.0, $pl['pos_revenue']);
        $this->assertSame(300.0, $pl['pos_cogs']); // 150 * 2
        $this->assertSame(200.0, $pl['gross_profit']); // 500 - 300
        $this->assertSame(100.0, $pl['expenses_total']);
        $this->assertSame(100.0, $pl['net_profit']); // 200 - 100
    }
}
