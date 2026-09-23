<?php

namespace App\Models;

use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'max_uses',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CouponUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * @param  Builder<Coupon>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public const TYPE_SECOND_ITEM = 'second_item_percentage';

    /**
     * "X% off the second bottle" — applies to the cheapest unit when two or more are bought.
     */
    public function isSecondItem(): bool
    {
        return $this->type === self::TYPE_SECOND_ITEM;
    }

    /**
     * Short Bangla summary, e.g. "১৫% ছাড়, সর্বোচ্চ ৳৫০০".
     */
    public function summaryBn(): string
    {
        $value = bn_digits(rtrim(rtrim(number_format((float) $this->value, 2, '.', ''), '0'), '.'));

        $text = match (true) {
            $this->isSecondItem() => "২য় বোতলে {$value}% ছাড়",
            $this->isPercentage() => "{$value}% ছাড়",
            default => bn_price($this->value).' ছাড়',
        };

        if ($this->max_discount_amount !== null) {
            $text .= ', সর্বোচ্চ '.bn_price($this->max_discount_amount);
        }

        return $text;
    }

    public function isPercentage(): bool
    {
        return $this->type === 'percentage';
    }
}
