<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantAttributeValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Storefront product listing: filtering, sorting, pagination and filter facets.
 */
class CatalogService
{
    /**
     * Sort keys accepted by the shop page.
     *
     * @var list<string>
     */
    public const SORTS = ['best_selling', 'newest', 'price_asc', 'price_desc', 'top_rated'];

    public const MIN_RATING = 4.5;

    private const ONE_SIZE = 'One Size';

    /**
     * Filter, sort and paginate active products.
     *
     * @param  array{q?: ?string, types?: list<string>, colors?: list<string>, sizes?: list<string>, min?: ?int, max?: ?int, in_stock?: bool, top_rated?: bool, on_sale?: bool, sort?: ?string}  $filters
     * @return array{products: LengthAwarePaginator<int, Product>, variant_count: int}
     */
    public function search(array $filters, ?Category $category, int $perPage): array
    {
        $query = $this->baseQuery($category);
        $this->applyFilters($query, $filters);

        $variantCount = ProductVariant::query()
            ->active()
            ->whereIn('product_id', (clone $query)->select('products.id'))
            ->count();

        $query->with(['categories', 'variants.attributeValues.attribute', 'variants.media', 'media'])
            ->withMin('activeVariants', 'price')
            ->withSum('orderItems', 'quantity');

        $this->applySort($query, $filters['sort'] ?? null);

        return [
            'products' => $query->paginate($perPage)->withQueryString(),
            'variant_count' => $variantCount,
        ];
    }

    /**
     * Filter options that make sense for what the shopper is currently looking at.
     *
     * Each attribute's options come from the products matching every *other* active filter
     * (so picking "Attar" drops T-shirt sizes), options already selected always stay visible,
     * and a facet with a single option is dropped. Sizes are withheld when the matching
     * products span several categories, since their scales (S–XL, 36–42, ml) don't mix.
     *
     * Attribute facets (type, colour, size) only appear once the listing is narrowed to something
     * coherent: a category is chosen, an attribute filter arrived in the URL (e.g. a home-page
     * link), or everything matching belongs to one category (e.g. a focused search). Until then
     * the "all products" page offers just category, price and the toggles.
     *
     * @param  array{q?: ?string, types?: list<string>, colors?: list<string>, sizes?: list<string>, min?: ?int, max?: ?int, in_stock?: bool, top_rated?: bool, on_sale?: bool}  $filters
     * @return array{types: list<string>, colors: list<string>, sizes: list<string>, attributes_need_category: bool, sizes_need_category: bool, price_min: int, price_max: int}
     */
    public function facets(array $filters, ?Category $category): array
    {
        $variantsMatching = function (array $except) use ($filters, $category): Builder {
            $products = $this->baseQuery($category);
            $this->applyFilters($products, array_merge($filters, $except));

            return ProductVariant::query()->active()->whereIn('product_id', $products->select('products.id'));
        };

        $valuesFor = function (string $slug, string $filterKey) use ($filters, $variantsMatching): array {
            $values = VariantAttributeValue::query()
                ->whereIn('variant_id', $variantsMatching([$filterKey => []])->select('id'))
                ->whereHas('attribute', fn (Builder $q) => $q->where('slug', $slug))
                ->distinct()
                ->pluck('value')
                ->reject(fn (string $value) => $value === self::ONE_SIZE)
                ->all();

            $selected = $filters[$filterKey] ?? [];
            $values = array_values(array_unique([...$values, ...$selected]));

            return count($values) > 1 || $selected !== [] ? $this->sortOptions($values) : [];
        };

        $priceVariants = $variantsMatching(['min' => null, 'max' => null]);
        $price = [
            'price_min' => (int) floor((float) (clone $priceVariants)->min('price')),
            'price_max' => (int) ceil((float) (clone $priceVariants)->max('price')),
        ];

        $hasAttributeFilter = ($filters['types'] ?? []) !== [] || ($filters['colors'] ?? []) !== [] || ($filters['sizes'] ?? []) !== [];

        if ($category === null && ! $hasAttributeFilter && $this->categoryCount($variantsMatching([])) > 1) {
            return ['types' => [], 'colors' => [], 'sizes' => [], 'attributes_need_category' => true, 'sizes_need_category' => false] + $price;
        }

        $sizes = $valuesFor('size', 'sizes');
        $sizesNeedCategory = $category === null && $sizes !== [] && ($filters['sizes'] ?? []) === []
            && $this->sizeCategoryCount($variantsMatching(['sizes' => []])) > 1;

        return [
            'types' => $valuesFor('type', 'types'),
            'colors' => $valuesFor('color', 'colors'),
            'sizes' => $sizesNeedCategory ? [] : $sizes,
            'attributes_need_category' => false,
            'sizes_need_category' => $sizesNeedCategory,
        ] + $price;
    }

    /**
     * How many categories the given variants' products belong to.
     *
     * @param  Builder<ProductVariant>  $variants
     */
    private function categoryCount(Builder $variants): int
    {
        return DB::table('product_categories')
            ->whereIn('product_id', $variants->select('product_variants.product_id'))
            ->distinct()
            ->count('category_id');
    }

    /**
     * How many categories contribute real sizes (not "One Size") among the given variants.
     *
     * @param  Builder<ProductVariant>  $variants
     */
    private function sizeCategoryCount(Builder $variants): int
    {
        return VariantAttributeValue::query()
            ->join('product_variants', 'product_variants.id', '=', 'variant_attribute_values.variant_id')
            ->join('product_categories', 'product_categories.product_id', '=', 'product_variants.product_id')
            ->whereIn('variant_attribute_values.variant_id', $variants->select('product_variants.id'))
            ->whereHas('attribute', fn (Builder $q) => $q->where('slug', 'size'))
            ->where('variant_attribute_values.value', '!=', self::ONE_SIZE)
            ->distinct()
            ->count('product_categories.category_id');
    }

    /**
     * Apparel sizes in wear order (XS → XXL), then numbers ascending (36, 42; "12 ml", "50 ml"), then A–Z.
     *
     * @param  list<string>  $values
     * @return list<string>
     */
    private function sortOptions(array $values): array
    {
        $apparel = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

        usort($values, function (string $a, string $b) use ($apparel): int {
            $rank = function (string $value) use ($apparel): array {
                $upper = strtoupper(trim($value));
                if (($index = array_search($upper, $apparel, true)) !== false) {
                    return [0, $index, $upper];
                }
                if (preg_match('/^\d+(\.\d+)?/', latin_digits($value), $m)) {
                    return [1, (float) $m[0], $upper];
                }

                return [2, 0, $upper];
            };

            return $rank($a) <=> $rank($b);
        });

        return $values;
    }

    /**
     * Active, visible products eager-loaded for product cards.
     *
     * @return Builder<Product>
     */
    public function cardQuery(): Builder
    {
        return $this->visible(Product::query())
            ->with(['categories', 'variants.attributeValues.attribute', 'variants.media', 'media'])
            ->withSum('orderItems', 'quantity');
    }

    /**
     * Best sellers by units sold in the last `$days` days (featured products fill any gap).
     *
     * @return Collection<int, Product>
     */
    public function bestSellers(int $limit = 4, int $days = 30): Collection
    {
        return $this->cardQuery()
            ->withSum(['orderItems as recent_units' => fn (Builder $q) => $q->where('order_items.created_at', '>=', now()->subDays($days))], 'quantity')
            ->orderByDesc('recent_units')
            ->orderByDesc('is_featured')
            ->orderByDesc('reviews_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Products whose cheapest variant is discounted, biggest discount first.
     *
     * @return Collection<int, Product>
     */
    public function onSale(int $limit = 4): Collection
    {
        return $this->cardQuery()
            ->where('is_combo', false)
            ->whereHas('activeVariants', fn (Builder $q) => $q->whereColumn('compare_at_price', '>', 'price'))
            ->get()
            ->sortByDesc(function (Product $product): float {
                $variant = $product->variants->where('is_active', true)->sortBy('price')->first();

                return $variant && $variant->compare_at_price > 0
                    ? ((float) $variant->compare_at_price - (float) $variant->price) / (float) $variant->compare_at_price
                    : 0.0;
            })
            ->take($limit)
            ->values();
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    private function visible(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereHas('activeVariants')
            ->where(fn (Builder $q) => $q
                ->where('hide_when_out_of_stock', false)
                ->orWhereHas('activeVariants', fn (Builder $v) => $v->whereColumn('stock_quantity', '>', 'reserved_quantity')));
    }

    /**
     * @return Builder<Product>
     */
    private function baseQuery(?Category $category): Builder
    {
        return $this->visible(Product::query())
            ->when($category, fn (Builder $q) => $q->whereHas(
                'categories',
                fn (Builder $c) => $c->where('categories.id', $category->id),
            ));
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array{q?: ?string, types?: list<string>, colors?: list<string>, sizes?: list<string>, min?: ?int, max?: ?int, in_stock?: bool, top_rated?: bool, on_sale?: bool, sort?: ?string}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if ($term = trim((string) ($filters['q'] ?? ''))) {
            $query->where(fn (Builder $q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('short_description', 'like', "%{$term}%"));
        }

        foreach (['type' => $filters['types'] ?? [], 'color' => $filters['colors'] ?? [], 'size' => $filters['sizes'] ?? []] as $slug => $values) {
            if ($values !== []) {
                $query->whereHas('activeVariants.attributeValues', fn (Builder $q) => $q
                    ->whereHas('attribute', fn (Builder $a) => $a->where('slug', $slug))
                    ->whereIn('value', $values));
            }
        }

        $min = $filters['min'] ?? null;
        $max = $filters['max'] ?? null;

        if ($min !== null || $max !== null) {
            $query->whereHas('activeVariants', fn (Builder $q) => $q
                ->when($min !== null, fn (Builder $v) => $v->where('price', '>=', $min))
                ->when($max !== null, fn (Builder $v) => $v->where('price', '<=', $max)));
        }

        if ($filters['in_stock'] ?? false) {
            $query->whereHas('activeVariants', fn (Builder $q) => $q->whereColumn('stock_quantity', '>', 'reserved_quantity'));
        }

        if ($filters['on_sale'] ?? false) {
            $query->whereHas('activeVariants', fn (Builder $q) => $q->whereColumn('compare_at_price', '>', 'price'));
        }

        if ($filters['top_rated'] ?? false) {
            $query->where('rating', '>=', self::MIN_RATING);
        }
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySort(Builder $query, ?string $sort): void
    {
        match ($sort) {
            'newest' => $query->latest('products.created_at'),
            'price_asc' => $query->orderBy('active_variants_min_price'),
            'price_desc' => $query->orderByDesc('active_variants_min_price'),
            'top_rated' => $query->orderByDesc('rating')->orderByDesc('reviews_count'),
            default => $query->orderByDesc('order_items_sum_quantity')->orderByDesc('is_featured')->orderByDesc('reviews_count'),
        };

        $query->orderBy('products.id');
    }
}
