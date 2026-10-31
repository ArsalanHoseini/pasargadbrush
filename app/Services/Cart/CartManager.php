<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;

class CartManager
{
    /**
     * Upper bound on one cart line, however many times "add to cart" is
     * pressed — larger B2B quantities go through the quote-request flow.
     */
    public const MAX_QUANTITY = 1000;

    public function addItem(Cart $cart, ProductVariant $variant, int $quantity): CartItem
    {
        $item = $cart->items()->where('product_variant_id', $variant->id)->first();

        if ($item) {
            $item->update(['quantity' => min($item->quantity + $quantity, self::MAX_QUANTITY)]);

            return $item;
        }

        return $cart->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => min($quantity, self::MAX_QUANTITY),
        ]);
    }

    /**
     * The quantity a line would have after adding $quantity more.
     */
    public function quantityAfterAdding(Cart $cart, ProductVariant $variant, int $quantity): int
    {
        $existing = (int) $cart->items()->where('product_variant_id', $variant->id)->value('quantity');

        return min($existing + $quantity, self::MAX_QUANTITY);
    }

    public function updateQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity <= 0) {
            $item->delete();

            return;
        }

        $item->update(['quantity' => min($quantity, self::MAX_QUANTITY)]);
    }

    /**
     * Every line that can't currently be ordered, keyed by cart item id —
     * e.g. the product was deactivated, switched to quote-only, or ran out
     * of stock after it was added. Empty when the whole cart is orderable.
     *
     * @return array<int, string>
     */
    public function problems(Cart $cart): array
    {
        $problems = [];

        foreach ($cart->items as $item) {
            $reason = $item->variant->unavailableReason($item->quantity);

            if ($reason !== null) {
                $problems[$item->id] = $reason;
            }
        }

        return $problems;
    }

    /**
     * Always computed from the live variant price — a cart row never stores
     * its own price, so a later price change is reflected automatically and
     * nothing here can be manipulated by a stale or tampered client value.
     */
    public function totalRial(Cart $cart): int
    {
        return $cart->items->sum(fn (CartItem $item) => $item->quantity * $item->variant->price_rial);
    }
}
