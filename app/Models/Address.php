<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'customer_id',
        'is_saved',
        'label',
        'province',
        'city',
        'address_line',
        'postal_code',
        'recipient_name',
        'recipient_phone',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_saved' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
