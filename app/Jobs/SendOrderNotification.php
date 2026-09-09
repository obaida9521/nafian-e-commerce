<?php

namespace App\Jobs;

use App\Mail\OrderStatusMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $event,
    ) {}

    public function handle(): void
    {
        $order = Order::with('items', 'user')->find($this->orderId);

        if (! $order) {
            return;
        }

        $email = $order->user?->email ?? $order->guest_email;

        if (! $email) {
            Log::warning('Order notification skipped: no recipient email', [
                'order' => $order->order_number,
                'event' => $this->event,
            ]);

            return;
        }

        Mail::to($email)->send(new OrderStatusMail($order, $this->event));
    }
}
