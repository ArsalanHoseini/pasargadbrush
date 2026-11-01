<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tag;
use Illuminate\Http\Response;

/**
 * /sitemap.xml (with product images, for Google Images) and a robots.txt
 * that points to it. Only public, indexable pages are listed — never cart,
 * checkout, account, search or admin.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $products = Product::query()
            ->with(['images', 'schematics'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'name', 'updated_at']);

        return response()
            ->view('sitemap.index', [
                'categories' => ProductCategory::query()->where('is_active', true)->get(['slug', 'updated_at']),
                'products' => $products,
                'posts' => BlogPost::query()->published()->latest('published_at')->get(['slug', 'updated_at']),
                'pages' => Page::query()->where('status', 'published')->get(['slug', 'updated_at']),
                'tags' => Tag::query()->whereHas('posts', fn ($query) => $query->published())->get(['slug', 'updated_at']),
                'latestUpdate' => collect([$products->max('updated_at'), BlogPost::query()->published()->max('updated_at')])->filter()->max(),
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /account',
            'Disallow: /login',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /order-tracking',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
