<?php

namespace App\Exports;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements FromQuery<Order>
 * @implements WithMapping<Order>
 */
class RevenueOrdersExport implements FromQuery, Responsable, WithHeadings, WithMapping
{
    use Exportable;

    private string $fileName = 'orders.xlsx';

    public function __construct(
        private readonly Carbon $from,
        private readonly Carbon $to,
    ) {}

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Order>
     */
    public function query()
    {
        return Order::query()
            ->whereBetween('created_at', [$this->from->copy()->startOfDay(), $this->to->copy()->endOfDay()])
            ->latest();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Order #', 'Date', 'Customer', 'Status', 'Payment', 'Subtotal', 'Discount', 'Delivery', 'Total'];
    }

    /**
     * @param  Order  $order
     * @return array<int, mixed>
     */
    public function map($order): array
    {
        return [
            $order->order_number,
            $order->created_at->format('Y-m-d H:i'),
            $order->shipping_name,
            $order->status->label(),
            $order->payment_method->label(),
            $order->subtotal,
            $order->discount_amount,
            $order->delivery_charge,
            $order->total_amount,
        ];
    }
}
