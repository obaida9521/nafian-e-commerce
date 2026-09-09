<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case COD = 'cod';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::COD => 'Cash on Delivery',
            self::Online => 'Online Payment',
        };
    }
}
