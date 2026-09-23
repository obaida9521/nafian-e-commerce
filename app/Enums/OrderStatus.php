<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * Admin panel (Bangla) label.
     */
    public function labelBn(): string
    {
        return match ($this) {
            self::Pending => 'নতুন',
            self::Confirmed => 'নিশ্চিত',
            self::Processing => 'প্রসেসিং',
            self::Shipped => 'শিপড',
            self::Delivered => 'ডেলিভারড',
            self::Cancelled => 'বাতিল',
            self::Refunded => 'রিফান্ড',
        };
    }

    /**
     * Customer-facing (Bangla) label used on tracking and confirmation.
     */
    public function customerLabel(): string
    {
        return match ($this) {
            self::Pending => 'অর্ডার গ্রহণ করা হয়েছে',
            self::Confirmed => 'অর্ডার নিশ্চিত হয়েছে',
            self::Processing => 'প্যাকিং চলছে',
            self::Shipped => 'পথে আছে',
            self::Delivered => 'ডেলিভারড',
            self::Cancelled => 'বাতিল',
            self::Refunded => 'রিফান্ড হয়েছে',
        };
    }

    /**
     * Pill colours from the design palette.
     *
     * @return array{bg: string, color: string}
     */
    public function pill(): array
    {
        [$bg, $color] = match ($this) {
            self::Pending, self::Confirmed => ['#EDF2F7', '#3C5A78'],
            self::Processing, self::Shipped => ['#F3EFEC', '#3E3532'],
            self::Delivered => ['#F1F4F1', '#3F5A42'],
            self::Cancelled, self::Refunded => ['#F7EFEE', '#8A5A52'],
        };

        return ['bg' => $bg, 'color' => $color];
    }

    /**
     * Whether a shopper may still cancel the order themselves.
     */
    public function isCustomerCancellable(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], true);
    }

    /**
     * Tailwind color token used by the status-badge component.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Confirmed => 'blue',
            self::Processing => 'indigo',
            self::Shipped => 'purple',
            self::Delivered => 'green',
            self::Cancelled => 'red',
            self::Refunded => 'gray',
        };
    }

    /**
     * @return array<int, self>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered, self::Cancelled],
            self::Delivered => [self::Refunded],
            self::Cancelled => [],
            self::Refunded => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->nextStatuses(), true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled, self::Refunded], true);
    }
}
