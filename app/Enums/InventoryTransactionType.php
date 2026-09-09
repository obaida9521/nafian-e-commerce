<?php

namespace App\Enums;

enum InventoryTransactionType: string
{
    case Purchase = 'purchase';
    case Sale = 'sale';
    case Return = 'return';
    case Adjustment = 'adjustment';
    case Reservation = 'reservation';
    case ReservationRelease = 'reservation_release';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Purchase',
            self::Sale => 'Sale',
            self::Return => 'Return',
            self::Adjustment => 'Adjustment',
            self::Reservation => 'Reservation',
            self::ReservationRelease => 'Reservation Release',
        };
    }
}
