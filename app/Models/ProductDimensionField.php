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

    public const TYPE_NUMBER = 'number';

    /** A choice from a fixed list of options, e.g. «جنس الیاف»: PA / PET / PP. */
    public const TYPE_CHOICE = 'choice';

    public const UNITS = [
        'mm' => 'میلی‌متر',
        'cm' => 'سانتی‌متر',
        'm' => 'متر',
        'inch' => 'اینچ',
    ];

    protected $fillable = [
        'product_id',
        'type',
        'code',
        'label',
        'unit',
        'options',
        'is_required',
        'min_value',
        'max_value',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'min_value' => 'float',
        'max_value' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isChoice(): bool
    {
        return $this->type === self::TYPE_CHOICE;
    }

    public function unitLabel(): string
    {
        return $this->isChoice() ? '' : (self::UNITS[$this->unit] ?? $this->unit);
    }
}
