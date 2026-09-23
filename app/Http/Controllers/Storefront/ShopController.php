<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\AnalyticsService;
use App\Services\CatalogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function __construct(
        private CatalogService $catalog,
        private AnalyticsService $analytics,
    ) {}

    public function index(Request $request, ?Category $category = null): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'types' => ['nullable', 'array'],
            'types.*' => ['string', 'max:100'],
            'colors' => ['nullable', 'array'],
            'colors.*' => ['string', 'max:100'],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['string', 'max:100'],
            'min' => ['nullable', 'integer', 'min:0'],
            'max' => ['nullable', 'integer', 'min:0'],
            'sort' => ['nullable', Rule::in(CatalogService::SORTS)],
        ]);

        $filters = [
            'q' => $validated['q'] ?? null,
            'types' => array_values(array_filter($validated['types'] ?? [])),
            'colors' => array_values(array_filter($validated['colors'] ?? [])),
            'sizes' => array_values(array_filter($validated['sizes'] ?? [])),
            'min' => isset($validated['min']) ? (int) $validated['min'] : null,
            'max' => isset($validated['max']) ? (int) $validated['max'] : null,
            'in_stock' => $request->boolean('in_stock'),
            'top_rated' => $request->boolean('top_rated'),
            'on_sale' => $request->boolean('on_sale'),
            'sort' => $validated['sort'] ?? 'best_selling',
        ];

        $categories = Category::query()
            ->active()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('sort_order')
            ->get();

        $result = $this->catalog->search($filters, $category, (int) config('shop.per_page'));

        $title = $category?->name ?? __('সব পণ্য');

        if ($filters['q'] && $request->integer('page', 1) === 1) {
            $this->analytics->search($filters['q']);
        }
        $this->analytics->viewItemList($result['products']->items(), $filters['q'] ? 'search' : $title);

        return view('storefront.plp', [
            'title' => $title,
            'activeCategory' => $category,
            'categories' => $categories,
            'products' => $result['products'],
            'variantCount' => $result['variant_count'],
            'filters' => $filters,
            'facets' => $this->catalog->facets($filters, $category),
        ]);
    }
}
