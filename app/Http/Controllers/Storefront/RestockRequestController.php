<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RestockRequestController extends Controller
{
    /**
     * Record a shopper's request to hear when a sold-out product is back.
     */
    public function store(Request $request, Product $product, AnalyticsService $analytics): RedirectResponse|JsonResponse
    {
        abort_unless($product->is_active, 404);

        $validated = $request->validate([
            'contact' => ['required', 'string', 'max:120', 'regex:/^([^@\s]+@[^@\s]+\.[^@\s]+|\+?[0-9][0-9\s-]{7,16})$/'],
        ], [
            'contact.regex' => __('সঠিক ইমেইল বা মোবাইল নম্বর দিন।'),
        ]);

        $restockRequest = $product->restockRequests()->firstOrCreate(
            ['contact' => trim($validated['contact'])],
            ['user_id' => auth('web')->id()],
        );

        if ($restockRequest->wasRecentlyCreated) {
            $analytics->lead('restock', $restockRequest->contact);
        }

        $message = __('স্টকে এলে আমরা আপনাকে জানাব।');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'analytics' => $analytics->pull()]);
        }

        return back()->with('success', $message);
    }
}
