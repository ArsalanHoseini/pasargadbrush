<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'name_snapshot',
        'sku_snapshot',
        'unit_price_rial',
        'quantity',
        'customization',
        'line_total_rial',
    ];

    protected $casts = [
        'customization' => 'array',
        'unit_price_rial' => 'integer',
        'line_total_rial' => 'integer',
    ];

    /**
     * A quote-only line placed from the cart: no price until staff set one
     * while confirming the order's prices.
     */
    public function isAwaitingPrice(Order $order): bool
    {
        return $order->requires_price_confirmation && $this->product_variant_id === null && $this->unit_price_rial === 0;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
