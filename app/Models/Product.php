<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'slug',
        'name',
        'primary_category_id',
        'type',
        'short_description',
        'description',
        'specifications',
        'stock_status',
        'is_featured',
        'is_active',
        'meta_title',
        'meta_description',
        'canonical_url',
        'og_image_media_id',
        'schematic_media_id',
        'sort_order',
        'published_at',
    ];

    protected $casts = [
        'specifications' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function isQuoteOnly(): bool
    {
        return $this->type === 'quote';
    }

    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'primary_category_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class, 'category_product', 'product_id', 'category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function dimensionFields(): HasMany
    {
        return $this->hasMany(ProductDimensionField::class)->orderBy('sort_order');
    }

    public function schematic(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'schematic_media_id');
    }

    /**
     * A product with a dimensions form can only be ordered (or quoted) with
     * the customer's dimensions attached.
     */
    public function requiresDimensions(): bool
    {
        return $this->dimensionFields->isNotEmpty();
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'attribute_value_product');
    }

    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_image_media_id');
    }

    public function images(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'mediables')
            ->wherePivot('role', 'gallery')
            ->orderByPivot('sort_order');
    }
}
