<?php

namespace App\Models;

use App\Models\Concerns\RecordsSlugRedirects;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class ProductCategory extends Model
{
    use RecordsSlugRedirects;

    public static function publicPathForSlug(string $slug): string
    {
        return '/categories/'.$slug;
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('nav.categories'));
        static::deleted(fn () => Cache::forget('nav.categories'));
    }

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
        'meta_title',
        'meta_description',
        'canonical_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Active categories that actually hold an active product — empty leftovers
     * of the WordPress import (e.g. «بدون دسته‌بندی», the Arabic «فرشاة الصناعی»)
     * stay out of menus and the sitemap.
     */
    public function scopeListable(Builder $query): void
    {
        $query->where('is_active', true)->where(fn (Builder $query) => $query
            ->whereHas('primaryProducts', fn (Builder $products) => $products->where('is_active', true))
            ->orWhereHas('products', fn (Builder $products) => $products->where('is_active', true)));
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product', 'category_id', 'product_id');
    }

    public function primaryProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'primary_category_id');
    }
}
