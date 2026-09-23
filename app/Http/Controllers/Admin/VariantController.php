<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;

/**
 * Variants are primarily managed inline on the product form. This controller
 * exposes the shallow resource routes for direct single-variant actions.
 */
class VariantController extends Controller
{
    public function index(Product $product): RedirectResponse
    {
        return redirect()->route('admin.products.edit', $product);
    }

    public function create(Product $product): RedirectResponse
    {
        return redirect()->route('admin.products.edit', $product);
    }

    public function edit(ProductVariant $variant): RedirectResponse
    {
        return redirect()->route('admin.products.edit', $variant->product_id);
    }

    public function destroy(ProductVariant $variant): RedirectResponse
    {
        $productId = $variant->product_id;
        $variant->delete();

        return redirect()->route('admin.products.edit', $productId)->with('success', 'ভ্যারিয়েন্ট সরানো হয়েছে।');
    }
}
