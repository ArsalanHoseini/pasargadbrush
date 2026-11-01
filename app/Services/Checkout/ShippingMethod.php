<?php

namespace App\Services\Checkout;

/**
 * How shipping is charged, chosen by staff at /admin/shipping. No method is
 * assumed by the code: until staff pick one, Announced applies, which is
 * exactly the pre-existing behavior (cost agreed by phone, entered per order).
 */
enum ShippingMethod: string
{
    /** Cost is agreed with the customer by phone; staff enter it on the order. */
    case Announced = 'announced';

    /** The customer pays the carrier on delivery (پس‌کرایه); the shop charges nothing. */
    case CashOnDelivery = 'cash_on_delivery';

    /** A fixed amount per order, optionally free above a subtotal threshold. */
    case Flat = 'flat';

    public function label(): string
    {
        return match ($this) {
            self::Announced => 'پس از هماهنگی اعلام می‌شود',
            self::CashOnDelivery => 'پس‌کرایه',
            self::Flat => 'مبلغ ثابت',
        };
    }
}
