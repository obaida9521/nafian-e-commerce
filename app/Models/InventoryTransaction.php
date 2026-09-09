<?php

namespace App\Models;

use App\Enums\InventoryTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only ledger. Never updated or deleted.
 */
class InventoryTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryTransactionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'variant_id',
        'type',
        'quantity_change',
        'stock_before',
        'stock_after',
        'reference_type',
        'reference_id',
        'reason',
        'created_by_type',
        'created_by_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InventoryTransactionType::class,
            'quantity_change' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
