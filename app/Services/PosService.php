<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\ProductVariant;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PosService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Ring up an over-the-counter cash sale: snapshot prices + cost, deduct
     * physical stock immediately, and record the takings. All-or-nothing.
     *
     * @param  array{
     *     items: array<int, array{variant_id: int, quantity: int}>,
     *     amount_paid: float, discount_amount?: float,
     *     customer_name?: string|null, customer_phone?: string|null, notes?: string|null
     * }  $data
     */
    public function createSale(array $data, Admin $cashier): Sale
    {
        if (empty($data['items'])) {
            throw ValidationException::withMessages(['items' => 'Add at least one item to the sale.']);
        }

        return DB::transaction(function () use ($data, $cashier): Sale {
            $variants = ProductVariant::with('product', 'attributeValues')
                ->whereIn('id', collect($data['items'])->pluck('variant_id'))
                ->get()
                ->keyBy('id');

            $subtotal = 0.0;
            $lines = [];

            foreach ($data['items'] as $item) {
                /** @var ProductVariant|null $variant */
                $variant = $variants->get($item['variant_id']);

                if (! $variant) {
                    throw new RuntimeException("Variant {$item['variant_id']} not found.");
                }

                $quantity = (int) $item['quantity'];
                $lineTotal = round((float) $variant->price * $quantity, 2);
                $subtotal += $lineTotal;

                $lines[] = [
                    'variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_name' => $variant->display_name,
                    'sku' => $variant->sku,
                    'unit_price' => $variant->price,
                    'cost_price' => $variant->cost_price ?? 0,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ];
            }

            $discount = round((float) ($data['discount_amount'] ?? 0), 2);
            $total = round($subtotal - $discount, 2);

            if ($total < 0) {
                throw ValidationException::withMessages(['discount_amount' => 'Discount cannot exceed the subtotal.']);
            }

            $paid = round((float) $data['amount_paid'], 2);

            if ($paid < $total) {
                throw ValidationException::withMessages(['amount_paid' => 'Amount paid is less than the total due.']);
            }

            $sale = Sale::create([
                'sale_number' => $this->generateSaleNumber(),
                'admin_id' => $cashier->id,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'amount_paid' => $paid,
                'change_due' => round($paid - $total, 2),
                'payment_method' => 'cash',
                'notes' => $data['notes'] ?? null,
            ]);

            $sale->items()->createMany($lines);

            // Deduct stock per line; throws InsufficientStockException -> rolls back.
            foreach ($lines as $line) {
                /** @var ProductVariant $variant */
                $variant = $variants->get($line['variant_id']);
                $this->inventory->recordPosSale($variant, $line['quantity'], $sale->id, $sale->sale_number);
            }

            $this->activityLogger->log(
                $cashier,
                'pos.sale_created',
                'sale',
                $sale->id,
                "POS sale {$sale->sale_number} for ".shop_price($total),
                null,
                ['total' => $total],
            );

            return $sale->load('items');
        });
    }

    public function generateSaleNumber(): string
    {
        $year = date('Y');

        return DB::transaction(function () use ($year): string {
            $count = Sale::whereYear('created_at', $year)->lockForUpdate()->count();
            $sequence = str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);

            return "POS-{$year}-{$sequence}";
        });
    }
}
