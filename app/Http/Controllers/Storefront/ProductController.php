<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load('variants.attributeValues.attribute', 'categories', 'reviews.user', 'media');

        $category = $product->categories->first();

        $related = Product::query()
            ->active()
            ->where('id', '!=', $product->id)
            ->when($category, fn ($q) => $q->whereHas('categories', fn ($c) => $c->where('categories.id', $category->id)))
            ->with('variants')
            ->take(4)
            ->get();

        if ($related->count() < 4) {
            $related = $related->merge(
                Product::active()
                    ->where('id', '!=', $product->id)
                    ->whereNotIn('id', $related->pluck('id'))
                    ->with(['variants', 'media'])
                    ->take(4 - $related->count())
                    ->get()
            );
        }

        $userReview = auth('web')->check()
            ? $product->reviews->firstWhere('user_id', auth('web')->id())
            : null;

        return view('storefront.pdp', [
            'product' => $product,
            'related' => $related,
            'colorOptions' => $product->colorOptions(),
            'sizeOptions' => $product->sizeOptions(),
            'userReview' => $userReview,
        ]);
    }

    public function storeReview(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:150'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $product->reviews()->updateOrCreate(
            ['user_id' => auth('web')->id()],
            [
                'rating' => $validated['rating'],
                'title' => $validated['title'] ?? null,
                'body' => $validated['body'] ?? null,
            ],
        );

        $product->recalculateRating();

        return redirect()
            ->route('store.product', $product->slug)
            ->with('success', 'Thanks — your review has been posted.')
            ->withFragment('reviews');
    }
}
