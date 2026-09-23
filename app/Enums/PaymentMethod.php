<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case COD = 'cod';
    case MobileBanking = 'mobile_banking';
    case Card = 'card';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::COD => 'Cash on Delivery',
            self::MobileBanking => 'bKash / Nagad',
            self::Card => 'Card',
            self::Online => 'Online Payment',
        };
    }

    /**
     * Storefront (Bangla) label.
     */
    public function labelBn(): string
    {
        return match ($this) {
            self::COD => 'ক্যাশ অন ডেলিভারি',
            self::MobileBanking => 'বিকাশ / নগদ',
            self::Card => 'কার্ড',
            self::Online => 'অনলাইন পেমেন্ট',
        };
    }

    /**
     * Short tag for dense tables (COD, বিকাশ, কার্ড).
     */
    public function shortLabelBn(): string
    {
        return match ($this) {
            self::COD => 'COD',
            self::MobileBanking => 'বিকাশ',
            self::Card => 'কার্ড',
            self::Online => 'অনলাইন',
        };
    }

    /**
     * Paid before delivery, so stock is held on the shorter online TTL.
     */
    public function isPrepaid(): bool
    {
        return $this !== self::COD;
    }
}
