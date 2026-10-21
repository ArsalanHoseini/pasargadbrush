<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'categories' => ProductCategory::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->withCount('primaryProducts')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'products' => Product::query()
                ->with(['images', 'primaryCategory'])
                ->where('is_active', true)
                ->latest('published_at')
                ->limit(8)
                ->get(),
            'posts' => BlogPost::query()
                ->with('featuredImage')
                ->where('status', 'published')
                ->latest('published_at')
                ->limit(3)
                ->get(),
        ]);
    }
}
