<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConversationMessage extends Model
{
    public const AUTHOR_CUSTOMER = 'customer';

    public const AUTHOR_STAFF = 'staff';

    protected $fillable = [
        'conversation_id',
        'author_type',
        'user_id',
        'body',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * The staff author (null for a customer's message).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function isFromStaff(): bool
    {
        return $this->author_type === self::AUTHOR_STAFF;
    }
}
