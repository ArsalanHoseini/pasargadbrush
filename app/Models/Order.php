<?php

namespace App\Models;

use App\Services\Checkout\ShippingMethod;
use App\Services\Checkout\ShippingQuote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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

    /** Placed by the customer through the website checkout. */
    public const SOURCE_WEBSITE = 'website';

    /** Entered by staff in the dashboard (phone order, quote request, ...). */
    public const SOURCE_MANUAL = 'manual';

    /**
     * The customer-facing progress steps, in order; "cancelled" is shown
     * separately rather than as a step.
     */
    public const PROGRESS_STEPS = ['pending', 'confirmed', 'processing', 'shipped', 'completed'];

    protected $fillable = [
        'order_number',
        'source',
        'created_by_user_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'status',
        'shipment_tracking_code',
        'payment_status',
        'shipping_address_id',
        'shipping_method',
        'subtotal_rial',
        'shipping_cost_rial',
        'total_rial',
        'requires_price_confirmation',
        'notes',
        'placed_at',
    ];

    protected $casts = [
        'placed_at' => 'datetime',
        'requires_price_confirmation' => 'boolean',
    ];

    /**
     * How this order's shipping reads, from the method it was placed with —
     * never from the current settings. Under Announced (and for orders placed
     * before shipping methods existed) the cost is unknown until staff enter
     * a non-zero amount on the order.
     */
    public function shippingQuote(): ShippingQuote
    {
        $method = ShippingMethod::tryFrom((string) $this->shipping_method) ?? ShippingMethod::Announced;

        return match ($method) {
            ShippingMethod::Announced => new ShippingQuote($method, $this->shipping_cost_rial > 0 ? $this->shipping_cost_rial : null),
            ShippingMethod::CashOnDelivery, ShippingMethod::Flat => new ShippingQuote($method, $this->shipping_cost_rial),
        };
    }

    /**
     * A human-friendly unique number, e.g. PB-20261101-K3X9QZ.
     */
    public static function generateOrderNumber(): string
    {
        do {
            $candidate = 'PB-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (static::query()->where('order_number', $candidate)->exists());

        return $candidate;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

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
