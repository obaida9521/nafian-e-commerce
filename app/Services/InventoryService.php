<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Exceptions\InsufficientStockException;
use App\Models\Admin;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    /**
     * Reserve stock for every item on an order. All-or-nothing.
     *
     * @throws InsufficientStockException
     */
    public function reserveStock(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $ttlMinutes = $order->payment_method->value === 'online'
                ? (int) config('shop.reservation_ttl_minutes.online', 15)
                : (int) config('shop.reservation_ttl_minutes.cod', 1440);

            $expiresAt = Carbon::now()->addMinutes($ttlMinutes);

            foreach ($order->items as $item) {
                if ($item->variant_id === null) {
                    continue;
                }

                /** @var ProductVariant $variant */
                $variant = ProductVariant::lockForUpdate()->findOrFail($item->variant_id);

                $available = $variant->stock_quantity - $variant->reserved_quantity;

                if ($available < $item->quantity) {
                    throw InsufficientStockException::forVariant(
                        $variant->display_name,
                        $available,
                        $item->quantity,
                    );
                }

                $variant->increment('reserved_quantity', $item->quantity);

                StockReservation::create([
                    'order_id' => $order->id,
                    'variant_id' => $variant->id,
                    'quantity' => $item->quantity,
                    'status' => 'active',
                    'expires_at' => $expiresAt,
                ]);

                $this->recordTransaction(
                    $variant,
                    InventoryTransactionType::Reservation,
                    0, // reservation does not change physical stock
                    $variant->stock_quantity,
                    $variant->stock_quantity,
                    'order',
                    $order->id,
                    'Stock reserved for order '.$order->order_number,
                );
            }
        });
    }

    /**
     * Release active reservations back to available stock (e.g. on cancel/expire).
     */
    public function releaseReservation(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $reservations = StockReservation::where('order_id', $order->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                /** @var ProductVariant $variant */
                $variant = ProductVariant::lockForUpdate()->findOrFail($reservation->variant_id);

                $variant->decrement('reserved_quantity', min($reservation->quantity, $variant->reserved_quantity));
                $reservation->update(['status' => 'released']);

                $this->recordTransaction(
                    $variant,
                    InventoryTransactionType::ReservationRelease,
                    0,
                    $variant->stock_quantity,
                    $variant->stock_quantity,
                    'order',
                    $order->id,
                    'Reservation released for order '.$order->order_number,
                );
            }
        });
    }

    /**
     * Convert active reservations into a completed sale (deduct physical stock).
     */
    public function convertReservationToSale(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $reservations = StockReservation::where('order_id', $order->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                /** @var ProductVariant $variant */
                $variant = ProductVariant::lockForUpdate()->findOrFail($reservation->variant_id);

                $stockBefore = $variant->stock_quantity;

                $variant->decrement('stock_quantity', min($reservation->quantity, $variant->stock_quantity));
                $variant->decrement('reserved_quantity', min($reservation->quantity, $variant->reserved_quantity));
                $reservation->update(['status' => 'converted']);

                $this->recordTransaction(
                    $variant,
                    InventoryTransactionType::Sale,
                    -$reservation->quantity,
                    $stockBefore,
                    $variant->fresh()->stock_quantity,
                    'order',
                    $order->id,
                    'Sale fulfilled for order '.$order->order_number,
                );
            }
        });
    }

    /**
     * Deduct physical stock immediately for an over-the-counter POS sale.
     * No reservation step — the goods leave the shelf at point of sale.
     *
     * @throws InsufficientStockException
     */
    public function recordPosSale(ProductVariant $variant, int $quantity, int $saleId, string $saleNumber): void
    {
        DB::transaction(function () use ($variant, $quantity, $saleId, $saleNumber): void {
            /** @var ProductVariant $locked */
            $locked = ProductVariant::lockForUpdate()->findOrFail($variant->id);

            $available = $locked->stock_quantity - $locked->reserved_quantity;

            if ($available < $quantity) {
                throw InsufficientStockException::forVariant($locked->display_name, $available, $quantity);
            }

            $stockBefore = $locked->stock_quantity;
            $locked->decrement('stock_quantity', $quantity);

            $this->recordTransaction(
                $locked,
                InventoryTransactionType::Sale,
                -$quantity,
                $stockBefore,
                $stockBefore - $quantity,
                'sale',
                $saleId,
                'POS sale '.$saleNumber,
            );
        });
    }

    /**
     * Manually adjust physical stock and log it against an admin.
     */
    public function adjustStock(ProductVariant $variant, int $quantity, string $reason, Admin $admin): void
    {
        DB::transaction(function () use ($variant, $quantity, $reason, $admin): void {
            /** @var ProductVariant $locked */
            $locked = ProductVariant::lockForUpdate()->findOrFail($variant->id);

            $stockBefore = $locked->stock_quantity;
            $stockAfter = $stockBefore + $quantity;

            if ($stockAfter < 0) {
                throw InsufficientStockException::forVariant($locked->display_name, $stockBefore, abs($quantity));
            }

            $locked->update(['stock_quantity' => $stockAfter]);

            $this->recordTransaction(
                $locked,
                InventoryTransactionType::Adjustment,
                $quantity,
                $stockBefore,
                $stockAfter,
                'manual',
                null,
                $reason,
                'admin',
                $admin->id,
            );

            $this->activityLogger->log(
                $admin,
                'inventory.adjusted',
                'product_variant',
                $locked->id,
                "Adjusted stock for {$locked->sku} by {$quantity} ({$reason})",
                ['stock_quantity' => $stockBefore],
                ['stock_quantity' => $stockAfter],
            );
        });
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    public function getLowStockVariants(int $threshold = 5): Collection
    {
        return ProductVariant::with('product')
            ->where('is_active', true)
            ->whereRaw('(stock_quantity - reserved_quantity) <= ?', [$threshold])
            ->orderByRaw('(stock_quantity - reserved_quantity) asc')
            ->get();
    }

    private function recordTransaction(
        ProductVariant $variant,
        InventoryTransactionType $type,
        int $quantityChange,
        int $stockBefore,
        int $stockAfter,
        ?string $referenceType,
        ?int $referenceId,
        ?string $reason,
        string $createdByType = 'system',
        ?int $createdById = null,
    ): void {
        InventoryTransaction::create([
            'variant_id' => $variant->id,
            'type' => $type,
            'quantity_change' => $quantityChange,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reason' => $reason,
            'created_by_type' => $createdByType,
            'created_by_id' => $createdById,
        ]);
    }
}
