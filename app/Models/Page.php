<?php

namespace App\Models;

use App\Models\Concerns\RecordsSlugRedirects;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use RecordsSlugRedirects;

    public static function publicPathForSlug(string $slug): string
    {
        return '/page/'.$slug;
    }

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
