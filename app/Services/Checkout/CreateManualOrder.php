<?php

namespace App\Services\Checkout;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Services\Customization\DimensionForm;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registers an order entered by staff — after a phone call, or from a quote
 * request for a quote-only product. Unlike website checkout, staff may sell
 * any product (quote-only included) at the price they agreed, so prices are
 * final (no price confirmation) and product availability isn't enforced —
 * only tracked stock is, since cancelling the order later gives it back.
 */
class CreateManualOrder
{
    public function __construct(private DimensionForm $dimensionForm) {}

    /**
     * @param  array<string, mixed>  $data  Validated by StoreManualOrderRequest.
     * @param  Collection<int, Product>  $products  The lines' products, keyed by id.
     *
     * @throws ValidationException when tracked stock is insufficient.
     */
    public function handle(array $data, Collection $products, User $staff): Order
    {
        return DB::transaction(function () use ($data, $products, $staff) {
            $variants = $this->lockAndCheckStock($data['items']);

            // Same rule as website checkout: an existing customer is matched by
            // phone and never overwritten; the order keeps what was entered.
            $customer = Customer::query()->firstOrCreate(
                ['phone' => $data['phone']],
                ['name' => $data['name'], 'email' => $data['email'] ?? null],
            );

            $address = $customer->addresses()->create([
                'province' => $data['province'],
                'city' => $data['city'],
                'address_line' => $data['address_line'],
                'postal_code' => $data['postal_code'] ?? null,
                'recipient_name' => $data['name'],
                'recipient_phone' => $data['phone'],
            ]);

            $subtotal = 0;
            $lineItems = [];

            foreach ($data['items'] as $line) {
                $product = $products->get($line['product_id']);
                $variant = filled($line['product_variant_id'] ?? null) ? $variants->get((int) $line['product_variant_id']) : null;
                $unitPrice = (int) $line['unit_price_toman'] * 10;
                $lineTotal = $unitPrice * (int) $line['quantity'];
                $subtotal += $lineTotal;

                $lineItems[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'name_snapshot' => filled($line['name'] ?? null)
                        ? $line['name']
                        : $product->name.($variant ? ' — '.$variant->label : ''),
                    'sku_snapshot' => $variant?->sku ?? $product->sku,
                    'unit_price_rial' => $unitPrice,
                    'quantity' => (int) $line['quantity'],
                    'customization' => $this->dimensionForm->snapshot($product, $line),
                    'line_total_rial' => $lineTotal,
                ];
            }

            $method = ShippingMethod::from($data['shipping_method']);
            $shippingCost = $method === ShippingMethod::CashOnDelivery ? 0 : (int) ($data['shipping_cost_toman'] ?? 0) * 10;

            $order = $customer->orders()->create([
                'order_number' => Order::generateOrderNumber(),
                'source' => Order::SOURCE_MANUAL,
                'created_by_user_id' => $staff->id,
                'customer_name' => $data['name'],
                'customer_phone' => $data['phone'],
                'customer_email' => $data['email'] ?? null,
                'status' => $data['status'],
                'payment_status' => $data['payment_status'],
                'shipping_address_id' => $address->id,
                'shipping_method' => $method->value,
                'subtotal_rial' => $subtotal,
                'shipping_cost_rial' => $shippingCost,
                'total_rial' => $subtotal + $shippingCost,
                'requires_price_confirmation' => false,
                'notes' => $data['notes'] ?? null,
                'placed_at' => now(),
            ]);

            $order->items()->createMany($lineItems);

            foreach ($data['items'] as $line) {
                $variant = filled($line['product_variant_id'] ?? null) ? $variants->get((int) $line['product_variant_id']) : null;

                if ($variant?->stock_quantity !== null) {
                    $variant->decrement('stock_quantity', (int) $line['quantity']);
                }
            }

            if (filled($data['quote_request_id'] ?? null)) {
                QuoteRequest::query()->whereKey($data['quote_request_id'])->update([
                    'order_id' => $order->id,
                    'status' => 'closed',
                ]);
            }

            AuditLog::create([
                'user_id' => $staff->id,
                'action' => 'order.created_manually',
                'auditable_type' => Order::class,
                'auditable_id' => $order->id,
                'changes' => ['quote_request_id' => $data['quote_request_id'] ?? null, 'total_rial' => $order->total_rial],
                'ip_address' => request()->ip(),
            ]);

            return $order;
        });
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $lines
     * @return Collection<int, ProductVariant>
     *
     * @throws ValidationException
     */
    private function lockAndCheckStock(array $lines): Collection
    {
        $quantities = collect($lines)
            ->filter(fn (array $line) => filled($line['product_variant_id'] ?? null))
            ->groupBy(fn (array $line) => (int) $line['product_variant_id'])
            ->map(fn ($group) => $group->sum(fn (array $line) => (int) $line['quantity']));

        $variants = ProductVariant::query()
            ->whereIn('id', $quantities->keys())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $shortages = $variants
            ->filter(fn (ProductVariant $variant) => $variant->stock_quantity !== null && $quantities[$variant->id] > $variant->stock_quantity)
            ->map(fn (ProductVariant $variant) => "{$variant->label}: فقط {$variant->stock_quantity} عدد موجود است");

        if ($shortages->isNotEmpty()) {
            throw ValidationException::withMessages(['items' => 'موجودی کافی نیست — '.$shortages->implode('، ')]);
        }

        return $variants;
    }
}
