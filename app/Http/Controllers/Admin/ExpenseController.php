<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExpenseCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExpenseRequest;
use App\Models\Expense;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $month = $request->date('month') ?? Carbon::today();
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $expenses = Expense::with('admin')
            ->between($from, $to)
            ->orderByDesc('spent_on')
            ->paginate(config('shop.per_page'))
            ->withQueryString();

        $monthlyTotal = (float) Expense::between($from, $to)->sum('amount');

        $byCategory = Expense::between($from, $to)
            ->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'category' => $row->category instanceof ExpenseCategory ? $row->category : ExpenseCategory::from($row->category),
                'total' => (float) $row->total,
                'count' => (int) $row->count,
            ])
            ->all();

        return view('admin.expenses.index', [
            'expenses' => $expenses,
            'categories' => ExpenseCategory::cases(),
            'monthLabel' => $from->format('F Y'),
            'monthValue' => $from->toDateString(),
            'monthlyTotal' => $monthlyTotal,
            'byCategory' => $byCategory,
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $expense = Expense::create([
            ...$request->validated(),
            'admin_id' => auth('admin')->id(),
        ]);

        $this->activityLogger->log(
            auth('admin')->user(),
            'expense.created',
            'expense',
            $expense->id,
            "Recorded expense {$expense->title} (".shop_price($expense->amount).')',
        );

        return redirect()->route('admin.expenses.index')->with('success', 'খরচ যোগ হয়েছে।');
    }

    public function update(StoreExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($request->validated());

        $this->activityLogger->log(
            auth('admin')->user(),
            'expense.updated',
            'expense',
            $expense->id,
            "Updated expense {$expense->title}",
        );

        return redirect()->route('admin.expenses.index')->with('success', 'খরচ আপডেট হয়েছে।');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        $this->activityLogger->log(
            auth('admin')->user(),
            'expense.deleted',
            'expense',
            $expense->id,
            "Deleted expense {$expense->title}",
        );

        return redirect()->route('admin.expenses.index')->with('success', 'খরচ মুছে ফেলা হয়েছে।');
    }
}
