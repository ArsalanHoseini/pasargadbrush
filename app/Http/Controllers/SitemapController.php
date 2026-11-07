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
                'categories' => ProductCategory::query()->listable()->get(['slug', 'updated_at']),
                'products' => $products,
                'posts' => BlogPost::query()->published()->latest('published_at')->get(['slug', 'updated_at']),
                'pages' => Page::query()->where('status', 'published')->get(['slug', 'updated_at']),
                'tags' => Tag::query()->whereHas('posts', fn ($query) => $query->published())->get(['slug', 'updated_at']),
                'latestUpdate' => collect([$products->max('updated_at'), BlogPost::query()->published()->max('updated_at')])->filter()->max(),
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * /llms.txt (llmstxt.org): a plain Markdown map of the site for AI agents —
     * PageSpeed's "Agentic Browsing" check looks for it.
     */
    public function llms(): Response
    {
        $lines = [
            '# '.(setting('company_legal_name') ?: 'برس صنعت پاسارگاد'),
            '',
            '> تولیدکننده انواع برس و فرچه صنعتی، نظافتی و سفارشی در ایران از سال ۱۳۷۴ (Pasargad Brush — industrial brush manufacturer, Iran). خرید مستقیم از کارخانه؛ قیمت محصولات سفارشی پس از ثبت ابعاد اعلام می‌شود.',
            '',
            '- تلفن: '.setting('contact_mobile'),
            '- ایمیل: '.setting('contact_email'),
            '- آدرس: '.setting('contact_address'),
            '',
            '## دسته‌بندی محصولات',
            '',
        ];

        foreach (ProductCategory::query()->listable()->orderBy('sort_order')->orderBy('name')->get(['name', 'slug']) as $category) {
            $lines[] = "- [{$category->name}](".route('categories.show', $category).')';
        }

        $lines = [...$lines, '', '## محصولات', ''];

        foreach (Product::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['name', 'slug']) as $product) {
            $lines[] = "- [{$product->name}](".route('products.show', $product).')';
        }

        $lines = [
            ...$lines,
            '',
            '## صفحات',
            '',
            '- [درباره ما]('.route('pages.show', 'about-us').')',
            '- [تماس با ما]('.route('pages.show', 'contact-us').')',
            '- [مقالات]('.route('blog.index').')',
            '- [نقشه سایت]('.route('sitemap').')',
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
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
