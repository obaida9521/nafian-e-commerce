<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_number',
        'user_id',
        'guest_email',
        'guest_phone',
        'status',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'shipping_city',
        'shipping_area',
        'shipping_district',
        'shipping_postcode',
        'delivery_zone',
        'subtotal',
        'discount_amount',
        'delivery_charge',
        'total_amount',
        'coupon_id',
        'coupon_code',
        'payment_method',
        'notes',
        'admin_notes',
        'rider_name',
        'rider_phone',
        'cancelled_reason',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'delivery_charge' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Status changes in the order they happened.
     *
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return HasMany<StockReservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    /**
     * @return HasOne<StockReservation, $this>
     */
    public function activeReservation(): HasOne
    {
        return $this->hasOne(StockReservation::class)->where('status', 'active');
    }

    /**
     * Expected delivery date: next day inside Dhaka, three days elsewhere.
     */
    public function estimatedDeliveryDate(): Carbon
    {
        return ($this->shipped_at ?? $this->created_at)->copy()->addDays($this->delivery_zone === 'outside' ? 3 : 1);
    }

    /**
     * Steps for the customer tracking timeline, each marked done / current / upcoming.
     *
     * @return list<array{label: string, at: ?Carbon, note: ?string, state: string}>
     */
    public function trackingSteps(): array
    {
        $history = $this->relationLoaded('statusHistory') ? $this->statusHistory : $this->statusHistory()->get();
        $reached = $history->keyBy(fn ($row) => $row->status->value);

        if ($this->status === OrderStatus::Cancelled) {
            return [
                ['label' => OrderStatus::Pending->customerLabel(), 'at' => $this->created_at, 'note' => null, 'state' => 'done'],
                ['label' => 'বাতিল হয়েছে', 'at' => $this->cancelled_at, 'note' => $this->cancelled_reason, 'state' => 'current'],
            ];
        }

        $flow = [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered];
        $currentIndex = array_search($this->status, $flow, true);
        $steps = [[
            'label' => OrderStatus::Pending->customerLabel(),
            'at' => $this->created_at,
            'note' => null,
            'state' => $this->status === OrderStatus::Pending ? 'current' : 'done',
        ]];

        foreach ($flow as $index => $status) {
            $row = $reached->get($status->value);
            $state = match (true) {
                $row !== null && $status === $this->status => 'current',
                $row !== null => 'done',
                $currentIndex !== false && $index < $currentIndex => 'done',
                default => 'upcoming',
            };

            $note = $status === OrderStatus::Shipped && $this->rider_name
                ? 'রাইডার '.$this->rider_name.($this->rider_phone ? ' · '.bn_phone($this->rider_phone) : '')
                : null;

            $steps[] = [
                'label' => $status->customerLabel(),
                'at' => $row?->created_at,
                'note' => $state === 'upcoming' && $status === OrderStatus::Delivered
                    ? 'সম্ভাব্য '.bn_date($this->estimatedDeliveryDate())
                    : $note,
                'state' => $state,
            ];
        }

        return $steps;
    }

    /**
     * @param  Builder<Order>  $query
     */
    public function scopeByStatus(Builder $query, OrderStatus|string $status): void
    {
        $query->where('status', $status instanceof OrderStatus ? $status->value : $status);
    }

    /**
     * @param  Builder<Order>  $query
     */
    public function scopeForAdmin(Builder $query): void
    {
        $query->latest();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status->color();
    }
}
