<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\StockReservation;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireStockReservations implements ShouldQueue
{
    use Queueable;

    public function handle(InventoryService $inventory): void
    {
        $orderIds = StockReservation::query()
            ->where('status', 'active')
            ->where('expires_at', '<', now())
            ->distinct()
            ->pluck('order_id');

        foreach ($orderIds as $orderId) {
            $order = Order::find($orderId);

            if ($order && $order->status->value === 'pending') {
                $inventory->releaseReservation($order);
            }
        }
    }
}
