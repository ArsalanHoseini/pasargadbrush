<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteRequest extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'phone',
        'email',
        'company',
        'message',
        'requested_quantity',
        'customization',
        'order_id',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'customization' => 'array',
    ];

    /**
     * The order staff registered from this request, if any.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
