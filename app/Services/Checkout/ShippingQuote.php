<?php

namespace App\Services\Checkout;

use App\Support\Money;

/**
 * What shipping costs for a given order or cart, and how to describe it to
 * the customer. A null cost means the shop doesn't know the amount yet
 * (Announced), so no grand total can be shown.
 */
final readonly class ShippingQuote
{
    public function __construct(
        public ShippingMethod $method,
        public ?int $costRial,
    ) {}

    public function isTotalKnown(): bool
    {
        return $this->costRial !== null;
    }

    /**
     * The text for the "هزینه ارسال" row.
     */
    public function description(): string
    {
        return match (true) {
            $this->method === ShippingMethod::CashOnDelivery => 'پس‌کرایه — هنگام تحویل به باربری پرداخت می‌شود',
            $this->costRial === null => 'پس از هماهنگی اعلام می‌شود',
            $this->costRial === 0 => 'رایگان',
            default => Money::toman($this->costRial),
        };
    }
}
