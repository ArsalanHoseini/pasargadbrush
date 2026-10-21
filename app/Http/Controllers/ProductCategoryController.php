<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    private const SORTS = ['newest', 'name'];

    public function show(Request $request, ProductCategory $category): View
    {
        abort_unless($category->is_active, 404);

        $sort = in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'newest';
        $search = trim((string) $request->query('q', ''));

        $query = Product::query()
            ->with('images')
            ->where('is_active', true)
            ->where(function ($query) use ($category) {
                $query->where('primary_category_id', $category->id)
                    ->orWhereHas('categories', fn ($q) => $q->where('product_categories.id', $category->id));
            });

        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        match ($sort) {
            'name' => $query->orderBy('name'),
            default => $query->orderBy('sort_order')->latest('published_at'),
        };

        $products = $query->paginate(12)->withQueryString();

        return view('categories.show', [
            'category' => $category,
            'products' => $products,
            'sort' => $sort,
            'search' => $search,
        ]);
    }
}
