<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;

/**
 * Keeps tracked variant stock in step with an order's cancellation state:
 * PlaceOrder deducts stock when an order is placed, cancelling gives it back,
 * and un-cancelling deducts it again (only if it's still there). Variants
 * with a null stock_quantity aren't tracked and are left untouched.
 * Callers run these inside a DB transaction.
 */
class OrderStock
{
    public function release(Order $order): void
    {
        foreach ($this->lockedTrackedVariants($order) as [$item, $variant]) {
            $variant->increment('stock_quantity', $item->quantity);
        }
    }

    /**
     * @return array<int, string> Names of the order lines that no longer have
     *                            enough stock; empty (and stock deducted) on success.
     */
    public function reserve(Order $order): array
    {
        $lines = $this->lockedTrackedVariants($order);

        $shortages = [];
        foreach ($lines as [$item, $variant]) {
            if ($variant->stock_quantity < $item->quantity) {
                $shortages[] = $item->name_snapshot;
            }
        }

        if ($shortages !== []) {
            return $shortages;
        }

        foreach ($lines as [$item, $variant]) {
            $variant->decrement('stock_quantity', $item->quantity);
        }

        return [];
    }

    /**
     * @return array<int, array{0: OrderItem, 1: ProductVariant}>
     */
    private function lockedTrackedVariants(Order $order): array
    {
        $items = $order->items()->whereNotNull('product_variant_id')->get();

        $variants = ProductVariant::query()
            ->whereIn('id', $items->pluck('product_variant_id'))
            ->whereNotNull('stock_quantity')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        return $items
            ->filter(fn (OrderItem $item) => $variants->has($item->product_variant_id))
            ->map(fn (OrderItem $item) => [$item, $variants->get($item->product_variant_id)])
            ->values()
            ->all();
    }
}
