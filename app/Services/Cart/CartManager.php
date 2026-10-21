<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;

class CartManager
{
    public function addItem(Cart $cart, ProductVariant $variant, int $quantity): CartItem
    {
        $item = $cart->items()->where('product_variant_id', $variant->id)->first();

        if ($item) {
            $item->update(['quantity' => $item->quantity + $quantity]);

            return $item;
        }

        return $cart->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }

    public function updateQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity <= 0) {
            $item->delete();

            return;
        }

        $item->update(['quantity' => $quantity]);
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
