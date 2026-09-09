<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Models\Coupon;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function index(): View
    {
        $coupons = Coupon::latest()->paginate(config('shop.per_page'));

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create(): RedirectResponse
    {
        // Create/edit is handled by the modal on the index page.
        return redirect()->route('admin.coupons.index');
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        $coupon = Coupon::create($this->payload($request));

        $this->activityLogger->log(
            auth('admin')->user(),
            'coupon.created',
            'coupon',
            $coupon->id,
            "Created coupon {$coupon->code}",
        );

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created.');
    }

    public function edit(Coupon $coupon): RedirectResponse
    {
        // Create/edit is handled by the modal on the index page.
        return redirect()->route('admin.coupons.index');
    }

    public function update(StoreCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($this->payload($request));

        $this->activityLogger->log(
            auth('admin')->user(),
            'coupon.updated',
            'coupon',
            $coupon->id,
            "Updated coupon {$coupon->code}",
        );

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        $this->activityLogger->log(
            auth('admin')->user(),
            'coupon.deleted',
            'coupon',
            $coupon->id,
            "Deleted coupon {$coupon->code}",
        );

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(StoreCouponRequest $request): array
    {
        $data = $request->validated();
        $data['code'] = strtoupper($data['code']);
        $data['min_order_amount'] = $data['min_order_amount'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
