<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $homeCategories = Category::query()
            ->active()
            ->where('show_on_home', true)
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        $bestSellers = Product::query()
            ->active()
            ->featured()
            ->with(['categories', 'variants.attributeValues.attribute', 'media'])
            ->take(4)
            ->get();

        return view('storefront.home', [
            'homeCategories' => $homeCategories,
            'bestSellers' => $bestSellers,
        ]);
    }
}
