<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Live suggestions for the search overlay: matching names and product rows.
     */
    public function suggest(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));

        if (mb_strlen($term) < 1) {
            return response()->json(['suggestions' => [], 'products' => []]);
        }

        $products = Product::query()
            ->active()
            ->whereHas('activeVariants')
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('short_description', 'like', "%{$term}%"))
            ->with(['activeVariants.attributeValues.attribute', 'categories', 'media'])
            ->orderByDesc('reviews_count')
            ->limit(6)
            ->get();

        return response()->json([
            'suggestions' => $products->pluck('name')->take(3)->values(),
            'products' => $products->map(function (Product $product): array {
                $cheapest = $product->activeVariants->sortBy('price')->first();
                $size = $cheapest?->attributeValues->first(fn ($av) => $av->attribute?->slug === 'size')?->value;

                return [
                    'name' => $product->name,
                    'url' => route('store.product', $product->slug),
                    'meta' => collect([$size ? bn_digits($size) : null, bn_price($cheapest?->price)])->filter()->implode(' · '),
                    'image' => $product->getFirstMediaUrl('images', 'thumb') ?: null,
                    'tone' => $product->tone ?? '#C2BBB0',
                ];
            })->values(),
        ]);
    }
}
