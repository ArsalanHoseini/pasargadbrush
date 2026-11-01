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
        $order->loadMissing('items.variant.product.dimensionFields');

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

        if (! $variant instanceof ProductVariant) {
            return 'این قلم به‌صورت آنلاین قابل سفارش نیست — برای آن استعلام قیمت بفرستید یا به کارشناس پیام دهید.';
        }

        $reason = $variant->unavailableReason($this->manager->quantityAfterAdding($cart, $variant, $item->quantity));

        if ($reason !== null) {
            return $reason;
        }

        $customization = null;
        $product = $variant->product;

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

        $this->manager->addItem($cart, $variant, $item->quantity, $customization);
        $cart->unsetRelation('items');

        return null;
    }
}
