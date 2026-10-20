<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class BlogPost extends Model
{
    protected $fillable = [
        'blog_category_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'author_id',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'canonical_url',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'blog_post_tag');
    }

    public function featuredImage(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'mediables')
            ->wherePivot('role', 'featured');
    }
}
