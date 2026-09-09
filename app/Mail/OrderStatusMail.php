<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $event,
    ) {}

    public function envelope(): Envelope
    {
        $number = $this->order->order_number;

        $subject = match ($this->event) {
            'created' => "Your Nafian order {$number} is confirmed",
            'cancelled' => "Your Nafian order {$number} was cancelled",
            default => "Update on your order {$number}: {$this->order->status->label()}",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order', with: [
            'order' => $this->order,
            'event' => $this->event,
        ]);
    }
}
