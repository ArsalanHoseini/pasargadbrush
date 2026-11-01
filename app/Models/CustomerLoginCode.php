<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A one-time SMS login code (only its hash is stored). See
 * App\Services\CustomerAuth\LoginCodeManager.
 */
class CustomerLoginCode extends Model
{
    protected $fillable = [
        'phone',
        'code_hash',
        'attempts',
        'expires_at',
        'consumed_at',
        'ip_address',
    ];

    protected $hidden = ['code_hash'];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];
}
