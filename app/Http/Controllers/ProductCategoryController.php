<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function show(ProductCategory $category): View
    {
        abort_unless($category->is_active, 404);

        $products = Product::query()
            ->with('images')
            ->where('is_active', true)
            ->where(function ($query) use ($category) {
                $query->where('primary_category_id', $category->id)
                    ->orWhereHas('categories', fn ($q) => $q->where('product_categories.id', $category->id));
            })
            ->orderBy('sort_order')
            ->paginate(12);

        return view('categories.show', [
            'category' => $category,
            'products' => $products,
        ]);
    }
}
