<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <title>ইনভয়েস {{ $order->order_number }}</title>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Libre+Caslon+Display&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; padding: 40px; font-family: 'Hind Siliguri', sans-serif; color: #241C1A; background: #fff; }
        .wrap { max-width: 760px; margin: 0 auto; }
        .row { display: flex; justify-content: space-between; gap: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th { text-align: left; font-size: 13px; color: #6B605B; padding-bottom: 10px; }
        td { padding: 12px 0; border-top: 1px solid #F0ECE9; font-size: 14.5px; }
        .right { text-align: right; }
        .muted { color: #6B605B; }
        .total { font-size: 18px; font-weight: 600; }
        @media print { body { padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
<div class="wrap">
    <div class="row" style="align-items:flex-start;">
        <div>
            <div style="font-family:'Libre Caslon Display',serif;font-size:24px;letter-spacing:.3em;">NAFIAN</div>
            <div class="muted" style="margin-top:8px;font-size:14px;line-height:1.8;">
                {{ $general['store_address'] ?? '' }}<br>{{ bn_digits($general['support_phone'] ?? '') }}
            </div>
        </div>
        <div class="right">
            <div style="font-size:18px;font-weight:600;">ইনভয়েস {{ bn_digits($order->order_number) }}</div>
            <div class="muted" style="margin-top:6px;font-size:14px;">{{ bn_date($order->created_at) }}</div>
            <div class="muted" style="font-size:14px;">{{ $order->payment_method->labelBn() }}</div>
        </div>
    </div>

    <div style="margin-top:28px;font-size:14.5px;line-height:1.9;">
        <div style="font-weight:600;">গ্রাহক</div>
        {{ $order->shipping_name }}<br>
        {{ $order->shipping_address }}<br>
        {{ collect([$order->shipping_area, $order->shipping_city, bn_digits($order->shipping_postcode)])->filter()->implode(', ') }}<br>
        {{ bn_phone($order->shipping_phone) }}
    </div>

    <table>
        <thead><tr><th>পণ্য</th><th>দাম</th><th>পরিমাণ</th><th class="right">মোট</th></tr></thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}<div class="muted" style="font-size:13px;">{{ bn_digits($item->variant_name) }} · SKU {{ $item->sku }}</div></td>
                    <td>{{ bn_price($item->unit_price) }}</td>
                    <td>{{ bn_digits($item->quantity) }}</td>
                    <td class="right">{{ bn_price($item->line_total) }}</td>
                </tr>
            @endforeach
            <tr><td colspan="3" class="muted">সাবটোটাল</td><td class="right">{{ bn_price($order->subtotal) }}</td></tr>
            @if($order->discount_amount > 0)
                <tr><td colspan="3" class="muted">ছাড়@if($order->coupon_code) ({{ $order->coupon_code }})@endif</td><td class="right">−{{ bn_price($order->discount_amount) }}</td></tr>
            @endif
            <tr><td colspan="3" class="muted">ডেলিভারি</td><td class="right">{{ bn_price($order->delivery_charge) }}</td></tr>
            <tr><td colspan="3" class="total">সর্বমোট</td><td class="right total">{{ bn_price($order->total_amount) }}</td></tr>
        </tbody>
    </table>

    <div class="muted" style="margin-top:28px;font-size:13.5px;line-height:1.8;">
        ধন্যবাদ। সিল অক্ষত থাকলে ৭ দিনের মধ্যে রিটার্ন করা যাবে।
    </div>

    <button class="no-print" onclick="window.print()" style="margin-top:24px;background:#2A2220;color:#fff;border:0;border-radius:999px;padding:12px 24px;font:600 14px 'Hind Siliguri';cursor:pointer;display:inline-flex;align-items:center;gap:8px;"><x-ui.icon name="printer" :size="16" />প্রিন্ট করুন</button>
</div>
</body>
</html>
