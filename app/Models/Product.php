<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'notes_top',
        'notes_heart',
        'notes_base',
        'ingredients',
        'usage_instructions',
        'video_url',
        'tone',
        'tone2',
        'badge',
        'rating',
        'reviews_count',
        'is_active',
        'is_featured',
        'is_combo',
        'hide_when_out_of_stock',
        'meta_title',
        'meta_description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_combo' => 'boolean',
            'hide_when_out_of_stock' => 'boolean',
            'rating' => 'decimal:1',
            'reviews_count' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->nonQueued();
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true);
    }

    /**
     * Order lines sold for any of this product's variants.
     *
     * @return HasManyThrough<OrderItem, ProductVariant, $this>
     */
    public function orderItems(): HasManyThrough
    {
        return $this->hasManyThrough(OrderItem::class, ProductVariant::class, 'product_id', 'variant_id');
    }

    /**
     * @return HasMany<RestockRequest, $this>
     */
    public function restockRequests(): HasMany
    {
        return $this->hasMany(RestockRequest::class);
    }

    /**
     * @return HasMany<ProductReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->latest();
    }

    /**
     * Recompute the cached rating average and review count from reviews.
     */
    public function recalculateRating(): void
    {
        $count = $this->reviews()->count();

        $this->update([
            'reviews_count' => $count,
            'rating' => $count > 0 ? round((float) $this->reviews()->avg('rating'), 1) : null,
        ]);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * YouTube video id parsed from `video_url` (watch, youtu.be, shorts, embed and live links).
     */
    public function youtubeId(): ?string
    {
        return self::parseYoutubeId($this->video_url);
    }

    public static function parseYoutubeId(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $pattern = '~^(?:https?://)?(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~';

        return preg_match($pattern, trim($url), $matches) ? $matches[1] : null;
    }

    public function getThumbnailAttribute(): ?string
    {
        $media = $this->getFirstMedia('images');

        return $media?->getUrl('thumb');
    }

    /**
     * Distinct colour option names across the product's active variants.
     *
     * @return list<string>
     */
    public function colorOptions(): array
    {
        return $this->variants
            ->flatMap(fn (ProductVariant $v) => $v->attributeValues)
            ->filter(fn ($av) => optional($av->attribute)->slug === 'color')
            ->pluck('value')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Distinct size option names across the product's active variants.
     *
     * @return list<string>
     */
    public function sizeOptions(): array
    {
        return $this->variants
            ->flatMap(fn (ProductVariant $v) => $v->attributeValues)
            ->filter(fn ($av) => optional($av->attribute)->slug === 'size')
            ->pluck('value')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resolve the variant matching a colour + size selection.
     */
    public function findVariant(?string $color, ?string $size): ?ProductVariant
    {
        return $this->variants->first(function (ProductVariant $variant) use ($color, $size): bool {
            $values = $variant->attributeValues->mapWithKeys(
                fn ($av) => [optional($av->attribute)->slug => $av->value],
            );

            $colorOk = $color === null || ($values['color'] ?? null) === $color;
            $sizeOk = $size === null || ($values['size'] ?? null) === $size;

            return $colorOk && $sizeOk;
        });
    }

    public function getTotalStockAttribute(): int
    {
        return (int) $this->variants->sum(fn (ProductVariant $v) => $v->available_quantity);
    }

    /**
     * @return array{min: float, max: float}|null
     */
    public function getPriceRangeAttribute(): ?array
    {
        $prices = $this->variants->pluck('price');

        if ($prices->isEmpty()) {
            return null;
        }

        return [
            'min' => (float) $prices->min(),
            'max' => (float) $prices->max(),
        ];
    }
}
