<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Jobs\SendOrderNotification;
use App\Models\Admin;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class OrderService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly CouponService $coupons,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Create an order with price snapshots and a stock reservation.
     *
     * @param  array{
     *     items: array<int, array{variant_id: int, quantity: int}>,
     *     shipping_name: string, shipping_phone: string, shipping_address: string,
     *     shipping_city: string, shipping_district: string, shipping_area?: string|null,
     *     shipping_postcode?: string|null, delivery_zone?: string,
     *     payment_method: string, coupon_code?: string|null, delivery_charge?: float,
     *     guest_email?: string|null, guest_phone?: string|null, notes?: string|null
     * }  $data
     */
    public function createOrder(array $data, ?User $user = null): Order
    {
        return DB::transaction(function () use ($data, $user): Order {
            $variants = ProductVariant::with('product', 'attributeValues')
                ->whereIn('id', collect($data['items'])->pluck('variant_id'))
                ->get()
                ->keyBy('id');

            $subtotal = 0.0;
            $lines = [];

            foreach ($data['items'] as $item) {
                /** @var ProductVariant|null $variant */
                $variant = $variants->get($item['variant_id']);

                if (! $variant) {
                    throw new RuntimeException("Variant {$item['variant_id']} not found.");
                }

                $lineTotal = round((float) $variant->price * $item['quantity'], 2);
                $subtotal += $lineTotal;

                $lines[] = [
                    'variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_name' => $variant->display_name,
                    'sku' => $variant->sku,
                    'unit_price' => $variant->price,
                    'quantity' => $item['quantity'],
                    'line_total' => $lineTotal,
                ];
            }

            $discount = 0.0;
            $coupon = null;

            if (! empty($data['coupon_code'])) {
                $unitPrices = collect($lines)->flatMap(fn (array $line) => array_fill(0, $line['quantity'], (float) $line['unit_price']))->all();
                $coupon = $this->coupons->validate($data['coupon_code'], $subtotal, $user, count($unitPrices));
                $discount = $this->coupons->calculateDiscount($coupon, $subtotal, $unitPrices);
            }

            $deliveryCharge = (float) ($data['delivery_charge'] ?? 0);
            $total = round($subtotal - $discount + $deliveryCharge, 2);

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user?->id,
                'guest_email' => $data['guest_email'] ?? null,
                'guest_phone' => $data['guest_phone'] ?? null,
                'status' => OrderStatus::Pending,
                'shipping_name' => $data['shipping_name'],
                'shipping_phone' => $data['shipping_phone'],
                'shipping_address' => $data['shipping_address'],
                'shipping_city' => $data['shipping_city'],
                'shipping_area' => $data['shipping_area'] ?? null,
                'shipping_district' => $data['shipping_district'],
                'shipping_postcode' => $data['shipping_postcode'] ?? null,
                'delivery_zone' => $data['delivery_zone'] ?? 'inside',
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'delivery_charge' => $deliveryCharge,
                'total_amount' => $total,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
            ]);

            $order->items()->createMany($lines);

            $order->statusHistory()->create(['status' => OrderStatus::Pending, 'note' => 'ওয়েবসাইট']);

            $order->payments()->create([
                'method' => $data['payment_method'],
                'amount' => $total,
                'status' => 'pending',
            ]);

            // Reserves stock; throws InsufficientStockException -> rolls back the transaction.
            $this->inventory->reserveStock($order->load('items'));

            if ($coupon) {
                $this->coupons->markUsed($coupon, $order, $user);
            }

            SendOrderNotification::dispatch($order->id, 'created');

            return $order->load('items');
        });
    }

    /**
     * Transition an order to a new status, applying inventory side effects.
     */
    public function updateStatus(Order $order, OrderStatus $newStatus, Admin $admin): void
    {
        if (! $order->status->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => "Cannot change status from {$order->status->label()} to {$newStatus->label()}.",
            ]);
        }

        DB::transaction(function () use ($order, $newStatus, $admin): void {
            $oldStatus = $order->status;

            $timestamps = match ($newStatus) {
                OrderStatus::Shipped => ['shipped_at' => now()],
                OrderStatus::Delivered => ['delivered_at' => now()],
                OrderStatus::Cancelled => ['cancelled_at' => now()],
                default => [],
            };

            $order->update(['status' => $newStatus, ...$timestamps]);

            $order->statusHistory()->create(['status' => $newStatus, 'admin_id' => $admin->id, 'note' => $admin->name]);

            if ($newStatus === OrderStatus::Delivered) {
                $this->inventory->convertReservationToSale($order);
            }

            if ($newStatus === OrderStatus::Cancelled) {
                $this->inventory->releaseReservation($order);
            }

            $this->activityLogger->log(
                $admin,
                'order.status_changed',
                'order',
                $order->id,
                "Order {$order->order_number}: {$oldStatus->label()} → {$newStatus->label()}",
                ['status' => $oldStatus->value],
                ['status' => $newStatus->value],
            );
        });

        SendOrderNotification::dispatch($order->id, 'status_changed');
    }

    public function cancelOrder(Order $order, string $reason, Admin $admin): void
    {
        if (! $order->status->canTransitionTo(OrderStatus::Cancelled)) {
            throw ValidationException::withMessages([
                'status' => "Order {$order->order_number} cannot be cancelled from {$order->status->label()}.",
            ]);
        }

        DB::transaction(function () use ($order, $reason, $admin): void {
            $oldStatus = $order->status;

            $order->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_reason' => $reason,
            ]);

            $order->statusHistory()->create(['status' => OrderStatus::Cancelled, 'admin_id' => $admin->id, 'note' => $reason]);

            $this->inventory->releaseReservation($order);

            $this->activityLogger->log(
                $admin,
                'order.cancelled',
                'order',
                $order->id,
                "Order {$order->order_number} cancelled: {$reason}",
                ['status' => $oldStatus->value],
                ['status' => OrderStatus::Cancelled->value],
            );
        });

        SendOrderNotification::dispatch($order->id, 'cancelled');
    }

    /**
     * Let a shopper cancel their own order while it has not been packed yet.
     */
    public function cancelByCustomer(Order $order): void
    {
        if (! $order->status->isCustomerCancellable()) {
            throw ValidationException::withMessages([
                'order' => 'এই অর্ডারটি এখন আর বাতিল করা যাবে না।',
            ]);
        }

        DB::transaction(function () use ($order): void {
            $order->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_reason' => 'গ্রাহক বাতিল করেছেন',
            ]);

            $order->statusHistory()->create(['status' => OrderStatus::Cancelled, 'note' => 'গ্রাহক বাতিল করেছেন']);

            $this->inventory->releaseReservation($order);
        });

        SendOrderNotification::dispatch($order->id, 'cancelled');
    }

    /**
     * Next order number, e.g. NFN-1001, NFN-1002 …
     */
    public function generateOrderNumber(): string
    {
        $prefix = config('shop.order_number_prefix', 'NFN');

        return DB::transaction(function () use ($prefix): string {
            $count = Order::query()->lockForUpdate()->count();

            do {
                $number = $prefix.'-'.(1001 + $count++);
            } while (Order::where('order_number', $number)->exists());

            return $number;
        });
    }
}
