<?php

namespace App\Services\Checkout;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates a real order from a cart's live contents. Every price is read from
 * the product variant at this moment — never from the cart or the request —
 * so nothing submitted by the browser can affect what gets charged.
 *
 * Shipping comes from the method staff configured at /admin/shipping (see
 * ShippingCalculator); under the default "announced" method it's 0 here and
 * staff enter the agreed amount on the order afterwards.
 */
class PlaceOrder
{
    public function __construct(private ShippingCalculator $shipping) {}

    /**
     * @param  array{name: string, phone: string, email: ?string, province: string, city: string, address_line: string, postal_code: ?string}  $details
     */
    public function handle(Cart $cart, array $details): Order
    {
        $cart->loadMissing('items.variant.product');

        if ($cart->items->isEmpty()) {
            throw new RuntimeException('Cannot place an order from an empty cart.');
        }

        return DB::transaction(function () use ($cart, $details) {
            $variants = $this->lockAndVerifyVariants($cart);

            // Phone isn't verified (no OTP yet), so whoever types a number must
            // never be able to rewrite the existing customer's name/email — an
            // existing customer is only matched, never updated. What was
            // actually entered lives on the order's own contact snapshot.
            $customer = Customer::query()->firstOrCreate(
                ['phone' => $details['phone']],
                ['name' => $details['name'], 'email' => $details['email'] ?? null],
            );

            $address = $customer->addresses()->create([
                'province' => $details['province'],
                'city' => $details['city'],
                'address_line' => $details['address_line'],
                'postal_code' => $details['postal_code'] ?? null,
                'recipient_name' => $details['name'],
                'recipient_phone' => $details['phone'],
            ]);

            $subtotal = 0;
            $lineItems = [];

            foreach ($cart->items as $item) {
                $variant = $variants->get($item->product_variant_id);
                $lineTotal = $variant->price_rial * $item->quantity;
                $subtotal += $lineTotal;

                $lineItems[] = [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'name_snapshot' => $variant->product->name.' — '.$variant->label,
                    'sku_snapshot' => $variant->sku,
                    'unit_price_rial' => $variant->price_rial,
                    'quantity' => $item->quantity,
                    'customization' => $item->customization,
                    'line_total_rial' => $lineTotal,
                ];
            }

            $shipping = $this->shipping->quote($subtotal);
            $shippingCost = $shipping->costRial ?? 0;

            $order = $customer->orders()->create([
                'order_number' => Order::generateOrderNumber(),
                'source' => Order::SOURCE_WEBSITE,
                'customer_name' => $details['name'],
                'customer_phone' => $details['phone'],
                'customer_email' => $details['email'] ?? null,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'shipping_address_id' => $address->id,
                'shipping_method' => $shipping->method->value,
                'subtotal_rial' => $subtotal,
                'shipping_cost_rial' => $shippingCost,
                'total_rial' => $subtotal + $shippingCost,
                // Custom dimensions are priced at the variant's base price for now;
                // staff review the dimensions and confirm the final price.
                'requires_price_confirmation' => $cart->items->contains(fn ($item) => $item->customization !== null),
                'placed_at' => now(),
            ]);

            $order->items()->createMany($lineItems);

            foreach ($cart->items as $item) {
                $variant = $variants->get($item->product_variant_id);

                if ($variant->stock_quantity !== null) {
                    $variant->decrement('stock_quantity', $item->quantity);
                }
            }

            $cart->items()->delete();

            return $order;
        });
    }

    /**
     * Re-reads every variant in the cart with a row lock, then re-checks it
     * with the same rule the product page and cart use. The lock means two
     * customers checking out the last units at the same moment can't both
     * succeed. Throws before anything is written if any line fails.
     *
     * @return Collection<int, ProductVariant>
     *
     * @throws CartNotOrderable
     */
    private function lockAndVerifyVariants(Cart $cart): Collection
    {
        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', $cart->items->pluck('product_variant_id'))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $problems = [];
        $quantitiesByVariant = $cart->items
            ->groupBy('product_variant_id')
            ->map(fn ($items) => (int) $items->sum('quantity'));

        foreach ($cart->items as $item) {
            $variant = $variants->get($item->product_variant_id);
            $reason = $variant
                ? $variant->unavailableReason($quantitiesByVariant[$item->product_variant_id])
                : 'این محصول دیگر به‌صورت آنلاین قابل خرید نیست.';

            if ($reason !== null) {
                $problems[$item->id] = $reason;
            }
        }

        if ($problems !== []) {
            throw new CartNotOrderable($problems);
        }

        return $variants;
    }
}
