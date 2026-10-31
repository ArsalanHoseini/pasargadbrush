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
                <td style="border-bottom: 1px solid #eee;">{{ $item->name_snapshot }}</td>
                <td style="text-align: center; border-bottom: 1px solid #eee;">{{ $item->quantity }}</td>
                <td style="text-align: left; border-bottom: 1px solid #eee;" dir="ltr">{{ \App\Support\Money::toman($item->line_total_rial) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        {{-- Shipping isn't calculated at checkout yet — see orders/partials/totals.blade.php. --}}
        <tr>
            <td colspan="2"><strong>جمع کالاها</strong></td>
            <td style="text-align: left;" dir="ltr"><strong>{{ \App\Support\Money::toman($order->subtotal_rial) }}</strong></td>
        </tr>
        @if ($order->shipping_cost_rial > 0)
            <tr>
                <td colspan="2">هزینه ارسال</td>
                <td style="text-align: left;" dir="ltr">{{ \App\Support\Money::toman($order->shipping_cost_rial) }}</td>
            </tr>
            <tr>
                <td colspan="2"><strong>جمع کل</strong></td>
                <td style="text-align: left;" dir="ltr"><strong>{{ \App\Support\Money::toman($order->total_rial) }}</strong></td>
            </tr>
        @else
            <tr>
                <td colspan="2">هزینه ارسال</td>
                <td style="text-align: left;">پس از هماهنگی اعلام می‌شود</td>
            </tr>
        @endif
    </tfoot>
</table>
