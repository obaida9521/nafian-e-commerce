<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function orders(): View
    {
        $orders = Order::query()
            ->where('user_id', auth('web')->id())
            ->withCount('items')
            ->latest()
            ->get();

        return view('storefront.account.orders', [
            'tab' => 'orders',
            'orders' => $orders,
        ]);
    }

    public function show(Order $order): View
    {
        abort_unless($order->user_id === auth('web')->id(), 403);

        $order->load('items');

        return view('storefront.account.order-detail', [
            'tab' => 'orders',
            'order' => $order,
        ]);
    }

    public function profile(): View
    {
        return view('storefront.account.profile', [
            'tab' => 'profile',
            'user' => auth('web')->user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = auth('web')->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update($data);

        return back()->with('success', 'Profile updated.');
    }

    public function addresses(): View
    {
        return view('storefront.account.addresses', [
            'tab' => 'addresses',
            'addresses' => auth('web')->user()->addresses()->orderByDesc('is_default')->get(),
        ]);
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $data = $this->validateAddress($request);
        $user = auth('web')->user();

        if (! empty($data['is_default'])) {
            $user->addresses()->update(['is_default' => false]);
        }

        $user->addresses()->create($data);

        return back()->with('success', 'Address added.');
    }

    public function updateAddress(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth('web')->id(), 403);

        $data = $this->validateAddress($request);

        if (! empty($data['is_default'])) {
            auth('web')->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return back()->with('success', 'Address updated.');
    }

    public function destroyAddress(Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth('web')->id(), 403);

        $address->delete();

        return back()->with('success', 'Address removed.');
    }

    public function defaultAddress(Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth('web')->id(), 403);

        auth('web')->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return back()->with('success', 'Default address set.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAddress(Request $request): array
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $data['is_default'] = $request->boolean('is_default');

        return $data;
    }
}
