<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustInventoryRequest;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function index(Request $request): View
    {
        $threshold = (int) config('shop.low_stock_threshold', 5);

        $variants = ProductVariant::with('product')
            ->when($request->string('search')->toString(), function ($query, string $search) {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->when($request->boolean('low_stock'), function ($query) use ($threshold) {
                $query->whereRaw('(stock_quantity - reserved_quantity) <= ?', [$threshold]);
            })
            ->orderByRaw('(stock_quantity - reserved_quantity) asc')
            ->paginate(config('shop.per_page'))
            ->withQueryString();

        return view('admin.inventory.index', [
            'variants' => $variants,
            'threshold' => $threshold,
        ]);
    }

    public function adjust(AdjustInventoryRequest $request, ProductVariant $variant): RedirectResponse
    {
        $this->inventory->adjustStock(
            $variant,
            (int) $request->integer('quantity_change'),
            $request->string('reason')->toString(),
            auth('admin')->user(),
        );

        return redirect()->route('admin.inventory.index')->with('success', "{$variant->sku}-এর স্টক আপডেট হয়েছে।");
    }
}
