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
        'status',
        'admin_notes',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
