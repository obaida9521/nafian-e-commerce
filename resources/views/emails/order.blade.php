@php $symbol = config('shop.currency_symbol'); @endphp
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#fff2e3;font-family:Arial,Helvetica,sans-serif;color:#111;">
    <div style="max-width:560px;margin:0 auto;padding:32px 20px;">
        <div style="font-weight:700;font-size:20px;letter-spacing:0.24em;color:#691d2a;text-align:center;margin-bottom:24px;">NAFIAN</div>

        <div style="background:#fff;border:1px solid #EADBC4;border-radius:12px;padding:28px;">
            @if($event === 'created')
                <h1 style="font-size:22px;margin:0 0 8px;">Thank you for your order</h1>
                <p style="color:#6B7280;font-size:14px;margin:0 0 20px;">We've received your order and are getting it ready.</p>
            @elseif($event === 'cancelled')
                <h1 style="font-size:22px;margin:0 0 8px;">Your order was cancelled</h1>
                <p style="color:#6B7280;font-size:14px;margin:0 0 20px;">If this wasn't expected, reply to this email and we'll help.</p>
            @else
                <h1 style="font-size:22px;margin:0 0 8px;">Order update</h1>
                <p style="color:#6B7280;font-size:14px;margin:0 0 20px;">Your order is now <strong>{{ $order->status->label() }}</strong>.</p>
            @endif

            <div style="font-family:'Courier New',monospace;font-size:14px;color:#691d2a;background:#F8EAD6;display:inline-block;padding:6px 12px;border-radius:6px;margin-bottom:20px;">{{ $order->order_number }}</div>

            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                @foreach($order->items as $item)
                    <tr>
                        <td style="padding:8px 0;border-bottom:1px solid #F1E7D6;">{{ $item->product_name }}<br><span style="color:#9CA3AF;font-size:12px;">{{ $item->variant_name }} · ×{{ $item->quantity }}</span></td>
                        <td style="padding:8px 0;border-bottom:1px solid #F1E7D6;text-align:right;font-weight:bold;">{{ $symbol }}{{ number_format($item->line_total, 0) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td style="padding:12px 0;font-weight:bold;">Total</td>
                    <td style="padding:12px 0;text-align:right;font-weight:bold;">{{ $symbol }}{{ number_format($order->total_amount, 0) }}</td>
                </tr>
            </table>

            <div style="margin-top:20px;font-size:13px;color:#6B7280;line-height:1.6;">
                <strong style="color:#111;">Shipping to</strong><br>
                {{ $order->shipping_name }}<br>
                {{ $order->shipping_address }}<br>
                {{ $order->shipping_city }}, {{ $order->shipping_district }}
            </div>

            <div style="text-align:center;margin-top:26px;">
                <a href="{{ route('store.track', ['order' => $order->order_number]) }}" style="display:inline-block;background:#691d2a;color:#fff;text-decoration:none;font-size:14px;font-weight:bold;padding:12px 24px;border-radius:8px;">Track your order</a>
            </div>
        </div>

        <p style="text-align:center;color:#9CA3AF;font-size:12px;margin-top:20px;">© {{ date('Y') }} Nafian · Crafted with care</p>
    </div>
</body>
</html>
