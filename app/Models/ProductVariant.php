<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductVariant extends Model implements HasMedia
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'compare_at_price',
        'cost_price',
        'stock_quantity',
        'reserved_quantity',
        'weight_grams',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'weight_grams' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * One photo per variant (e.g. the 50 ml bottle); the product page switches to it on selection.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile()->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->nonQueued();
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<VariantAttributeValue, $this>
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(VariantAttributeValue::class, 'variant_id');
    }

    /**
     * @return HasMany<InventoryTransaction, $this>
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'variant_id');
    }

    /**
     * @param  Builder<ProductVariant>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function getAvailableQuantityAttribute(): int
    {
        return $this->stock_quantity - $this->reserved_quantity;
    }

    /**
     * Value of one attribute on this variant, e.g. attributeValue('size') → "12 ml".
     */
    public function attributeValue(string $slug): ?string
    {
        return $this->attributeValues->first(fn ($av) => $av->attribute?->slug === $slug)?->value;
    }

    /**
     * Short label for listings: size when present, otherwise every attribute value.
     */
    public function shortLabel(): string
    {
        $size = $this->attributeValue('size');

        if ($size !== null && $size !== 'One Size') {
            return $size;
        }

        return $this->attributeValues->first()?->value ?? $this->sku;
    }

    public function getDisplayNameAttribute(): string
    {
        $values = $this->attributeValues->pluck('value')->all();

        return $values ? implode(' / ', $values) : $this->sku;
    }
}
