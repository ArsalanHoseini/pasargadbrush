<table cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 560px;">
    <thead>
        <tr style="background: #f5f5f5;">
            <th style="text-align: right; border-bottom: 1px solid #ddd;">محصول</th>
            <th style="text-align: center; border-bottom: 1px solid #ddd;">تعداد</th>
            <th style="text-align: left; border-bottom: 1px solid #ddd;">مبلغ</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($order->items as $item)
            <tr>
                <td style="border-bottom: 1px solid #eee;">
                    {{ $item->name_snapshot }}
                    @foreach ($item->customization['dimensions'] ?? [] as $dimension)
                        <br><small style="color: #555;">{{ $dimension['code'] }} {{ $dimension['label'] }}: {{ $dimension['value'] }} {{ \App\Models\ProductDimensionField::UNITS[$dimension['unit']] ?? $dimension['unit'] }}</small>
                    @endforeach
                    @if (! empty($item->customization['notes']))
                        <br><small style="color: #555;">توضیحات: {{ $item->customization['notes'] }}</small>
                    @endif
                </td>
                <td style="text-align: center; border-bottom: 1px solid #eee;">{{ $item->quantity }}</td>
                <td style="text-align: left; border-bottom: 1px solid #eee;" dir="ltr">{{ \App\Support\Money::toman($item->line_total_rial) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        {{-- Same rule as orders/partials/totals.blade.php: no grand total while shipping is unknown. --}}
        @php $shipping = $order->shippingQuote(); @endphp
        <tr>
            <td colspan="2"><strong>جمع کالاها</strong></td>
            <td style="text-align: left;" dir="ltr"><strong>{{ \App\Support\Money::toman($order->subtotal_rial) }}</strong></td>
        </tr>
        <tr>
            <td colspan="2">هزینه ارسال</td>
            <td style="text-align: left;">{{ $shipping->description() }}</td>
        </tr>
        @if ($shipping->isTotalKnown())
            <tr>
                <td colspan="2"><strong>جمع کل</strong></td>
                <td style="text-align: left;" dir="ltr"><strong>{{ \App\Support\Money::toman($order->subtotal_rial + $shipping->costRial) }}</strong></td>
            </tr>
        @endif
    </tfoot>
</table>

@if ($order->requires_price_confirmation)
    <p style="color: #92400e; background: #fffbeb; padding: 8px;">
        قیمت اقلام با ابعاد سفارشی، قیمت پایه است و قیمت نهایی پس از بررسی ابعاد توسط کارشناس تأیید می‌شود.
    </p>
@endif
