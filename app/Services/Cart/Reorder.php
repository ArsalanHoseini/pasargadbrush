<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Services\Customization\DimensionForm;
use Illuminate\Support\Facades\Validator;

/**
 * Puts a past order's lines back into the cart, at today's prices (the cart
 * never stores prices). Each line gets the same checks as a normal "add to
 * cart": the variant must still be orderable online and in stock, and a
 * custom-dimension line's dimensions must still satisfy the product's
 * *current* dimensions form. Lines that fail are skipped with a reason.
 */
class Reorder
{
    public function __construct(
        private CartManager $manager,
        private DimensionForm $dimensionForm,
    ) {}

    /**
     * @return array{added: int, skipped: list<array{name: string, reason: string}>}
     */
    public function handle(Order $order, Cart $cart): array
    {
        $order->loadMissing('items.product.dimensionFields', 'items.variant.product.dimensionFields');

        $added = 0;
        $skipped = [];

        foreach ($order->items as $item) {
            $reason = $this->addLine($item, $cart);

            if ($reason === null) {
                $added++;
            } else {
                $skipped[] = ['name' => $item->name_snapshot, 'reason' => $reason];
            }
        }

        return ['added' => $added, 'skipped' => $skipped];
    }

    /**
     * @return string|null Why the line couldn't be added, or null on success.
     */
    private function addLine(OrderItem $item, Cart $cart): ?string
    {
        $variant = $item->variant;
        $product = $variant?->product ?? $item->product;

        if (! $product) {
            return 'این محصول دیگر در سایت وجود ندارد.';
        }

        // A line without a variant is a quote-only product, re-ordered without a price.
        $reason = $variant instanceof ProductVariant
            ? $variant->unavailableReason($this->manager->quantityAfterAdding($cart, $variant, $item->quantity))
            : $product->quoteOrderUnavailableReason();

        if ($reason !== null) {
            return $reason;
        }

        $customization = null;

        if ($product->requiresDimensions()) {
            $values = [
                'dimensions' => $this->dimensionForm->valuesFromSnapshot($product, $item->customization),
                DimensionForm::NOTES_INPUT => $item->customization['notes'] ?? null,
            ];

            if (Validator::make($values, $this->dimensionForm->rules($product))->fails()) {
                return 'فرم ابعاد این محصول تغییر کرده است — لطفاً از صفحه‌ی محصول، ابعاد را دوباره وارد کنید.';
            }

            $customization = $this->dimensionForm->snapshot($product, $values);
        }

        $this->manager->addItem($cart, $product, $variant, $item->quantity, $customization);
        $cart->unsetRelation('items');

        return null;
    }
}
