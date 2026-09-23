<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\Product;
use App\Services\ActivityLogger;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): View
    {
        $products = Product::with(['categories', 'variants.attributeValues', 'media'])
            ->withCount('variants')
            ->when($request->string('search')->toString(), function ($query, string $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->string('status') === 'active');
            })
            ->when($request->string('category')->toString(), function ($query, string $slug) {
                $query->whereHas('categories', fn ($q) => $q->where('slug', $slug));
            })
            ->latest()
            ->paginate(config('shop.per_page'))
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::active()->orderBy('sort_order')->get(),
        ]);
    }

    public function toggleFeatured(Product $product): RedirectResponse
    {
        $product->update(['is_featured' => ! $product->is_featured]);

        $this->activityLogger->log(
            auth('admin')->user(),
            'product.featured_toggled',
            'product',
            $product->id,
            ($product->is_featured ? 'Marked' : 'Unmarked')." {$product->name} as featured",
        );

        return back()->with('success', $product->is_featured ? 'ফিচার্ড করা হয়েছে।' : 'ফিচার্ড থেকে সরানো হয়েছে।');
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'product' => new Product,
            'categories' => Category::active()->orderBy('name')->get(),
            'attributes' => AttributeDefinition::orderBy('name')->get(),
            'selectedCategories' => [],
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->products->create(
            $request->validated(),
            $request->file('images', []),
        );

        $this->activityLogger->log(
            auth('admin')->user(),
            'product.created',
            'product',
            $product->id,
            "Created product {$product->name}",
        );

        return redirect()->route('admin.products.index')->with('success', 'পণ্য তৈরি হয়েছে।');
    }

    public function show(Product $product): RedirectResponse
    {
        return redirect()->route('admin.products.edit', $product);
    }

    public function edit(Product $product): View
    {
        $product->load('variants.attributeValues', 'categories', 'media');

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => Category::active()->orderBy('name')->get(),
            'attributes' => AttributeDefinition::orderBy('name')->get(),
            'selectedCategories' => $product->categories->pluck('id')->all(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update(
            $product,
            $request->validated(),
            $request->file('images', []),
        );

        $this->activityLogger->log(
            auth('admin')->user(),
            'product.updated',
            'product',
            $product->id,
            "Updated product {$product->name}",
        );

        return redirect()->route('admin.products.index')->with('success', 'পণ্য আপডেট হয়েছে।');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        $this->activityLogger->log(
            auth('admin')->user(),
            'product.deleted',
            'product',
            $product->id,
            "Deleted product {$product->name}",
        );

        return redirect()->route('admin.products.index')->with('success', 'পণ্য মুছে ফেলা হয়েছে।');
    }
}
