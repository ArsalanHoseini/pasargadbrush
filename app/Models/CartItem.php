<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A cart line: either a priced product's variant (product_variant_id set)
 * or a quote-only product (no variant, no price yet — staff price it after
 * the order is placed). product_id is always set.
 */
class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_id', 'product_variant_id', 'quantity', 'customization'];

    /**
     * The customer's dimensions/choices snapshot (see DimensionForm::snapshot()),
     * or null for a product without an order form.
     */
    protected $casts = [
        'customization' => 'array',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Today's unit price, or null when the line is priced by staff later.
     */
    public function unitPriceRial(): ?int
    {
        return $this->variant?->price_rial;
    }

    public function lineTotalRial(): ?int
    {
        $unit = $this->unitPriceRial();

        return $unit === null ? null : $unit * $this->quantity;
    }

    /**
     * The line's display title, e.g. «برس قالیشویی — بزرگ».
     */
    public function title(): string
    {
        return $this->product->name.($this->variant ? ' — '.$this->variant->label : '');
    }

    /**
     * @param  int  $quantity  For a variant line: the variant's total across the cart.
     */
    public function unavailableReason(int $quantity): ?string
    {
        return $this->variant
            ? $this->variant->unavailableReason($quantity)
            : $this->product->quoteOrderUnavailableReason();
    }
}
