<?php

namespace App\Services\Checkout;

/**
 * Applies the shipping settings staff configure at /admin/shipping to a cart
 * subtotal. Amounts are stored in Rial like every other price.
 */
class ShippingCalculator
{
    public const METHOD_SETTING = 'shipping_method';

    public const FLAT_RATE_SETTING = 'shipping_flat_rate_rial';

    public const FREE_THRESHOLD_SETTING = 'shipping_free_threshold_rial';

    public function currentMethod(): ShippingMethod
    {
        return ShippingMethod::tryFrom((string) setting(self::METHOD_SETTING)) ?? ShippingMethod::Announced;
    }

    public function quote(int $subtotalRial): ShippingQuote
    {
        $method = $this->currentMethod();

        return match ($method) {
            ShippingMethod::Announced => new ShippingQuote($method, null),
            ShippingMethod::CashOnDelivery => new ShippingQuote($method, 0),
            ShippingMethod::Flat => new ShippingQuote($method, $this->flatCost($subtotalRial)),
        };
    }

    private function flatCost(int $subtotalRial): int
    {
        $freeThreshold = (int) setting(self::FREE_THRESHOLD_SETTING);

        if ($freeThreshold > 0 && $subtotalRial >= $freeThreshold) {
            return 0;
        }

        return (int) setting(self::FLAT_RATE_SETTING);
    }
}
