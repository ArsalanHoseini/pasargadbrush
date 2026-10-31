<?php

namespace App\Services\Checkout;

use RuntimeException;

/**
 * Thrown by PlaceOrder when, at the moment of placing the order, one or more
 * cart lines can no longer be bought (deactivated, switched to quote-only,
 * out of stock, ...). Nothing has been written when this is thrown.
 */
class CartNotOrderable extends RuntimeException
{
    /**
     * @param  array<int, string>  $problems  Customer-facing reasons, keyed by cart item id.
     */
    public function __construct(public readonly array $problems)
    {
        parent::__construct('Cart contains items that can no longer be ordered.');
    }
}
