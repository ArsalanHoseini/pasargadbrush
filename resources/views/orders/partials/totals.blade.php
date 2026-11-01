{{--
    Items subtotal + shipping row + grand total, for a cart or a placed order.
    @param int $subtotalRial
    @param \App\Services\Checkout\ShippingQuote $shipping
    When the shipping cost isn't known yet (the "announced" method), no grand
    total is shown — the subtotal must never read as a final amount.
--}}
<div class="space-y-2 text-sm">
    <div @class(['flex justify-between', 'text-base font-bold' => ! $shipping->isTotalKnown()])>
        <span>جمع کالاها</span>
        <span dir="ltr">{{ \App\Support\Money::toman($subtotalRial) }}</span>
    </div>
    <div class="flex justify-between gap-4 text-neutral-600">
        <span class="shrink-0">هزینه ارسال</span>
        <span class="text-left">{{ $shipping->description() }}</span>
    </div>
    @if ($shipping->isTotalKnown())
        <div class="flex justify-between border-t border-neutral-100 pt-3 text-base font-bold">
            <span>جمع کل</span>
            <span dir="ltr">{{ \App\Support\Money::toman($subtotalRial + $shipping->costRial) }}</span>
        </div>
    @endif
    @if ($priceNeedsConfirmation ?? false)
        <p class="rounded bg-amber-50 p-2 text-xs text-amber-800">
            قیمت اقلام با ابعاد سفارشی، قیمت پایه است و قیمت نهایی پس از بررسی ابعاد توسط کارشناس تأیید و به شما اعلام می‌شود.
        </p>
    @endif
</div>
