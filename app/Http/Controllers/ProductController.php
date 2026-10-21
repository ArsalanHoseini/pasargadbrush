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

        return view('products.show', [
            'product' => $product->load(['images', 'primaryCategory', 'variants']),
            'related' => $related,
        ]);
    }
}
