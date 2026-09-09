<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Rent = 'rent';
    case Salary = 'salary';
    case Utilities = 'utilities';
    case Shipping = 'shipping';
    case Marketing = 'marketing';
    case Supplies = 'supplies';
    case Purchase = 'purchase';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Rent => 'Rent',
            self::Salary => 'Salaries',
            self::Utilities => 'Utilities',
            self::Shipping => 'Shipping & Courier',
            self::Marketing => 'Marketing',
            self::Supplies => 'Supplies',
            self::Purchase => 'Stock Purchase',
            self::Other => 'Other',
        };
    }

    /**
     * Swatch colour used for the badge and breakdown bar.
     */
    public function color(): string
    {
        return match ($this) {
            self::Rent => '#691d2a',
            self::Salary => '#2563EB',
            self::Utilities => '#D4A853',
            self::Shipping => '#0891B2',
            self::Marketing => '#DB2777',
            self::Supplies => '#7C3AED',
            self::Purchase => '#059669',
            self::Other => '#6B7280',
        };
    }
}
