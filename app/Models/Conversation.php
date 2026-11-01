<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A staff ↔ customer message thread — about an order, a quote request, or
 * a general ticket. Status says whose turn it is: "open" = waiting for
 * staff, "answered" = waiting for the customer. See PostConversationMessage.
 */
class Conversation extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_ANSWERED = 'answered';

    public const STATUS_CLOSED = 'closed';

    public const STAFF_STATUS_LABELS = [
        self::STATUS_OPEN => 'در انتظار پاسخ',
        self::STATUS_ANSWERED => 'پاسخ داده شد',
        self::STATUS_CLOSED => 'بسته شده',
    ];

    public const CUSTOMER_STATUS_LABELS = [
        self::STATUS_OPEN => 'در انتظار پاسخ کارشناس',
        self::STATUS_ANSWERED => 'پاسخ داده شد',
        self::STATUS_CLOSED => 'بسته شده',
    ];

    protected $fillable = [
        'customer_id',
        'order_id',
        'quote_request_id',
        'subject',
        'status',
        'customer_unread',
        'staff_unread',
        'last_message_at',
    ];

    protected $casts = [
        'customer_unread' => 'boolean',
        'staff_unread' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->oldest('id');
    }
}
