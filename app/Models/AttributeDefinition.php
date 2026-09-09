<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeDefinition extends Model
{
    /** @use HasFactory<\Database\Factories\AttributeDefinitionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * @return HasMany<VariantAttributeValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(VariantAttributeValue::class, 'attribute_id');
    }
}
