<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'body',
        'template',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'canonical_url',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}
