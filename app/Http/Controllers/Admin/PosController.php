<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePosSaleRequest;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Services\PosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(private readonly PosService $pos) {}

    public function index(): View
    {
        $variants = ProductVariant::query()
            ->where('is_active', true)
            ->with(['product:id,name,is_active,tone,tone2', 'product.media', 'attributeValues'])
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->orderBy('product_id')
            ->get()
            ->map(fn (ProductVariant $v): array => [
                'variant_id' => $v->id,
                'product' => $v->product->name,
                'variant' => $v->display_name,
                'sku' => $v->sku,
                'price' => (float) $v->price,
                'available' => $v->available_quantity,
                'image' => $v->product->thumbnail,
                'tone' => $v->product->tone ?? '#C2BBB0',
                'tone2' => $v->product->tone2 ?? '#A39B8E',
            ])
            ->values();

        return view('admin.pos.index', ['catalog' => $variants]);
    }

    public function store(StorePosSaleRequest $request): RedirectResponse
    {
        $sale = $this->pos->createSale($request->validated(), auth('admin')->user());

        return redirect()
            ->route('admin.pos.show', $sale)
            ->with('success', "বিক্রি {$sale->sale_number} সম্পন্ন হয়েছে।");
    }

    public function show(Sale $sale): View
    {
        $sale->load('items', 'admin');

        return view('admin.pos.receipt', compact('sale'));
    }
}
