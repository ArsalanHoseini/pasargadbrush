<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer's review of a product; public only once approved by staff.
 */
class ProductReview extends Model
{
    public const STATUS_LABELS = [
        'pending' => 'در انتظار تأیید',
        'approved' => 'تأییدشده',
        'spam' => 'اسپم',
    ];

    protected $fillable = ['product_id', 'customer_id', 'author_name', 'rating', 'body', 'status'];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Customers (by id) who have a non-cancelled order containing the
     * product — their reviews get a «خریدار این محصول» badge. One query for
     * the whole review list.
     *
     * @return list<int>
     */
    public static function verifiedBuyerIds(Product $product): array
    {
        return Order::query()
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('customer_id')
            ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
            ->distinct()
            ->pluck('customer_id')
            ->all();
    }
}
