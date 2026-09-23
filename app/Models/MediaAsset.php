<?php

namespace App\Models;

use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * An image uploaded directly into the admin media library, ready to be picked for
 * products, variants, categories or the brand logo.
 */
class MediaAsset extends Model implements HasMedia
{
    /** @use HasFactory<MediaAssetFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'admin_id',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile()->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(400)
            ->nonQueued();
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
