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
        'is_default' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
