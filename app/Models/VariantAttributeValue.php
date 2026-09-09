<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariantAttributeValue extends Model
{
    /** @use HasFactory<\Database\Factories\VariantAttributeValueFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'variant_id',
        'attribute_id',
        'value',
    ];

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * @return BelongsTo<AttributeDefinition, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class, 'attribute_id');
    }
}
