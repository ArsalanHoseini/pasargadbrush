<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_LABELS = [
        'pending' => 'در انتظار',
        'confirmed' => 'تأییدشده',
        'processing' => 'در حال پردازش',
        'shipped' => 'ارسال‌شده',
        'completed' => 'تکمیل‌شده',
        'cancelled' => 'لغوشده',
    ];

    public const PAYMENT_STATUS_LABELS = [
        'unpaid' => 'پرداخت‌نشده',
        'paid' => 'پرداخت‌شده',
        'failed' => 'ناموفق',
        'refunded' => 'بازگشت‌شده',
    ];

    /**
     * The customer-facing progress steps, in order; "cancelled" is shown
     * separately rather than as a step.
     */
    public const PROGRESS_STEPS = ['pending', 'confirmed', 'processing', 'shipped', 'completed'];

    protected $fillable = [
        'order_number',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'status',
        'shipment_tracking_code',
        'payment_status',
        'shipping_address_id',
        'subtotal_rial',
        'shipping_cost_rial',
        'total_rial',
        'notes',
        'placed_at',
    ];

    protected $casts = [
        'placed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
