<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'company_name',
        'phone',
        'phone_verified_at',
        'email',
        'password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * The customer's address book (not the frozen copies orders point at).
     */
    public function savedAddresses(): HasMany
    {
        return $this->hasMany(Address::class)->where('is_saved', true)->orderByDesc('is_default')->latest();
    }

    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class);
    }

    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }
}
