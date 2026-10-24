<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'products' => Product::query()
                ->with(['images', 'primaryCategory'])
                ->where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->limit(24)
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
