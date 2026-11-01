<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $related = Product::query()
            ->with('images')
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->where('primary_category_id', $product->primary_category_id)
            ->limit(4)
            ->get();

        $product->load(['images', 'schematics', 'primaryCategory', 'variants' => fn ($query) => $query->orderBy('sort_order'), 'dimensionFields']);
        $product->variants->each->setRelation('product', $product);

        return view('products.show', [
            'product' => $product,
            'related' => $related,
            'orderableVariants' => $product->variants->filter(fn ($variant) => $variant->unavailableReason() === null)->values(),
            'quoteOrderUnavailableReason' => $product->type === 'quote' ? $product->quoteOrderUnavailableReason() : null,
        ]);
    }
}
