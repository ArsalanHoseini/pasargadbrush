<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
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

        $product->load([
            'images', 'schematics', 'primaryCategory', 'dimensionFields', 'highlights.image', 'applications.icon',
            'variants' => fn ($query) => $query->orderBy('sort_order'),
        ]);
        $product->variants->each->setRelation('product', $product);

        $reviews = $product->approvedReviews()->get();

        return view('products.show', [
            'product' => $product,
            'related' => $related,
            'reviews' => $reviews,
            'averageRating' => $reviews->whereNotNull('rating')->avg('rating'),
            'verifiedBuyerIds' => $reviews->isEmpty() ? [] : ProductReview::verifiedBuyerIds($product),
            'orderableVariants' => $product->variants->filter(fn ($variant) => $variant->unavailableReason() === null)->values(),
            'quoteOrderUnavailableReason' => $product->type === 'quote' ? $product->quoteOrderUnavailableReason() : null,
        ]);
    }
}
