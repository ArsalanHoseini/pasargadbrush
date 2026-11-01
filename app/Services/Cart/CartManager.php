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

    /**
     * A line with customer dimensions is always its own line (the same
     * variant in two sizes is two different products to make); plain lines
     * of the same variant are merged.
     *
     * @param  array<string, mixed>|null  $customization  See DimensionForm::snapshot().
     */
    public function addItem(Cart $cart, ProductVariant $variant, int $quantity, ?array $customization = null): CartItem
    {
        if ($customization === null) {
            $item = $cart->items()
                ->where('product_variant_id', $variant->id)
                ->whereNull('customization')
                ->first();

            if ($item) {
                $item->update(['quantity' => min($item->quantity + $quantity, self::MAX_QUANTITY)]);

                return $item;
            }
        }

        return $cart->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => min($quantity, self::MAX_QUANTITY),
            'customization' => $customization,
        ]);
    }

    /**
     * How many of this variant the cart would hold in total after adding
     * $quantity more — stock is per variant, whatever the lines' dimensions.
     */
    public function quantityAfterAdding(Cart $cart, ProductVariant $variant, int $quantity): int
    {
        $existing = (int) $cart->items()->where('product_variant_id', $variant->id)->sum('quantity');

        return $existing + min($quantity, self::MAX_QUANTITY);
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
        $quantitiesByVariant = $this->quantitiesByVariant($cart);

        foreach ($cart->items as $item) {
            $reason = $item->variant->unavailableReason($quantitiesByVariant[$item->product_variant_id]);

            if ($reason !== null) {
                $problems[$item->id] = $reason;
            }
        }

        return $problems;
    }

    /**
     * Total quantity per variant across all of the cart's lines.
     *
     * @return array<int, int>
     */
    public function quantitiesByVariant(Cart $cart): array
    {
        return $cart->items
            ->groupBy('product_variant_id')
            ->map(fn ($items) => (int) $items->sum('quantity'))
            ->all();
    }

    /**
     * Whether any line carries customer dimensions — its price is then only
     * provisional until staff confirm it.
     */
    public function hasCustomizedItems(Cart $cart): bool
    {
        return $cart->items->contains(fn (CartItem $item) => $item->customization !== null);
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
