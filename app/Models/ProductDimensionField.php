<?php

namespace App\Models;

use Database\Factories\ProductDimensionFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One dimension a customer must give when ordering a product, e.g. code "A",
 * label "قطر خارجی", unit "mm" — the code matches the letter on the product's
 * schematic drawing.
 */
class ProductDimensionField extends Model
{
    /** @use HasFactory<ProductDimensionFieldFactory> */
    use HasFactory;

    public const UNITS = [
        'mm' => 'میلی‌متر',
        'cm' => 'سانتی‌متر',
        'm' => 'متر',
        'inch' => 'اینچ',
    ];

    protected $fillable = [
        'product_id',
        'code',
        'label',
        'unit',
        'is_required',
        'min_value',
        'max_value',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'min_value' => 'float',
        'max_value' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unitLabel(): string
    {
        return self::UNITS[$this->unit] ?? $this->unit;
    }
}
