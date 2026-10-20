<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoRedirect extends Model
{
    protected $fillable = [
        'from_path',
        'to_path',
        'status_code',
        'hits_count',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
