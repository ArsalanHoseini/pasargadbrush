{{--
    Shipping is not calculated at checkout yet (no shipping rule decided —
    see docs/DECISIONS.md), so an order's shipping_cost_rial is 0 until
    staff set it. Never present the items subtotal as a final total then.
--}}
<div class="mt-3 border-t border-neutral-100 pt-3 text-sm">
    @if ($order->shipping_cost_rial > 0)
        <div class="flex justify-between">
            <span>جمع کالاها</span>
            <span dir="ltr">{{ \App\Support\Money::toman($order->subtotal_rial) }}</span>
        </div>
        <div class="mt-2 flex justify-between">
            <span>هزینه ارسال</span>
            <span dir="ltr">{{ \App\Support\Money::toman($order->shipping_cost_rial) }}</span>
        </div>
        <div class="mt-3 flex justify-between border-t border-neutral-100 pt-3 text-base font-bold">
            <span>جمع کل</span>
            <span dir="ltr">{{ \App\Support\Money::toman($order->total_rial) }}</span>
        </div>
    @else
        <div class="flex justify-between text-base font-bold">
            <span>جمع کالاها</span>
            <span dir="ltr">{{ \App\Support\Money::toman($order->subtotal_rial) }}</span>
        </div>
        <div class="mt-2 flex justify-between text-neutral-600">
            <span>هزینه ارسال</span>
            <span>پس از هماهنگی اعلام می‌شود</span>
        </div>
    @endif
</div>
