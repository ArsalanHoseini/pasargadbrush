<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'label',
        'attributes',
        'price_rial',
        'stock_quantity',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'attributes' => 'array',
        'price_rial' => 'integer',
        'stock_quantity' => 'integer',
        'is_default' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The single rule for whether this variant can be bought online in the
     * given quantity — used by the product page, the cart, and (under a row
     * lock) the final order placement, so all three always agree. Returns a
     * customer-facing Persian reason, or null when the purchase is allowed.
     * A null stock_quantity means stock isn't tracked for this variant.
     */
    public function unavailableReason(int $quantity = 1): ?string
    {
        $product = $this->product;

        if (! $product || ! $product->is_active || $product->type !== 'priced') {
            return 'این محصول دیگر به‌صورت آنلاین قابل خرید نیست.';
        }

        if ($product->stock_status === 'out_of_stock' || $this->stock_quantity === 0) {
            return 'این محصول در حال حاضر ناموجود است.';
        }

        if ($this->price_rial <= 0) {
            return 'قیمت این محصول در حال به‌روزرسانی است؛ لطفاً با ما تماس بگیرید.';
        }

        if ($this->stock_quantity !== null && $quantity > $this->stock_quantity) {
            return "از این محصول فقط {$this->stock_quantity} عدد موجود است.";
        }

        return null;
    }
}
