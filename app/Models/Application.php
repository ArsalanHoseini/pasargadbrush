<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A use/industry a brush is made for (e.g. «شستشوی فرش»), with an icon,
 * defined once at /admin/applications and picked per product.
 */
class Application extends Model
{
    protected $fillable = ['name', 'icon_media_id', 'sort_order'];

    public function icon(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'icon_media_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
