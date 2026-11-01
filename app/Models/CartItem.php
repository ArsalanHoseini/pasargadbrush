<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_variant_id', 'quantity', 'customization'];

    /**
     * The customer's dimensions snapshot (see DimensionForm::snapshot()),
     * or null for a product without a dimensions form.
     */
    protected $casts = [
        'customization' => 'array',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
