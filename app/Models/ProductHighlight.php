<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A key feature shown beside the product image: a short title and an
 * optional close-up image.
 */
class ProductHighlight extends Model
{
    public const MAX_PER_PRODUCT = 4;

    protected $fillable = ['product_id', 'media_id', 'title', 'sort_order'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
