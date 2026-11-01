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

    /**
     * Technical drawings shown beside the order form, each dimension marked
     * by its letter. Same pivot as the gallery, role "schematic".
     */
    public function schematics(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'mediables')
            ->wherePivot('role', 'schematic')
            ->orderByPivot('sort_order');
    }

    /**
     * A product with an order form (dimensions and/or choices) can only be
     * ordered (or quoted) with the customer's answers attached.
     */
    public function requiresDimensions(): bool
    {
        return $this->dimensionFields->isNotEmpty();
    }

    /**
     * Why this quote-only product can't be ordered online right now (it's
     * then ordered without a price, which staff set afterwards), or null.
     * Priced products are checked per variant instead
     * (ProductVariant::unavailableReason()).
     */
    public function quoteOrderUnavailableReason(): ?string
    {
        if (! $this->is_active || $this->type !== 'quote') {
            return 'این محصول دیگر به‌صورت آنلاین قابل سفارش نیست.';
        }

        if ($this->stock_status === 'out_of_stock') {
            return 'این محصول در حال حاضر ناموجود است.';
        }

        return null;
    }

    public function highlights(): HasMany
    {
        return $this->hasMany(ProductHighlight::class)->orderBy('sort_order');
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', 'approved')->latest();
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
