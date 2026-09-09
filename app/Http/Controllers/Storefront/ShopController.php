<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request, ?Category $category = null): View
    {
        $categories = Category::query()
            ->active()
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        $sort = $request->string('sort', 'Featured')->toString();
        $maxPrice = (int) $request->integer('max', 500);
        $colors = array_filter((array) $request->input('colors', []));

        $query = Product::query()
            ->active()
            ->with(['categories', 'variants.attributeValues.attribute', 'media'])
            ->whereHas('variants', fn ($q) => $q->where('price', '<=', $maxPrice));

        if ($category) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id));
        }

        if ($colors !== []) {
            $query->whereHas('variants.attributeValues', function ($q) use ($colors): void {
                $q->whereHas('attribute', fn ($a) => $a->where('slug', 'color'))
                    ->whereIn('value', $colors);
            });
        }

        $products = $query->get();

        $products = match ($sort) {
            'Price: Low to High' => $products->sortBy(fn (Product $p) => $p->variants->min('price'))->values(),
            'Price: High to Low' => $products->sortByDesc(fn (Product $p) => $p->variants->min('price'))->values(),
            'Top Rated' => $products->sortByDesc('rating')->values(),
            default => $products->sortByDesc('is_featured')->values(),
        };

        $colorOptions = collect(config('shop.colors'))
            ->filter(fn ($hex, $name) => Product::active()
                ->whereHas('variants.attributeValues', fn ($q) => $q
                    ->whereHas('attribute', fn ($a) => $a->where('slug', 'color'))
                    ->where('value', $name))
                ->exists())
            ->keys()
            ->all();

        return view('storefront.plp', [
            'title' => $category?->name ?? 'All products',
            'activeCategory' => $category,
            'categories' => $categories,
            'products' => $products,
            'sort' => $sort,
            'maxPrice' => $maxPrice,
            'selectedColors' => $colors,
            'colorOptions' => $colorOptions,
        ]);
    }
}
