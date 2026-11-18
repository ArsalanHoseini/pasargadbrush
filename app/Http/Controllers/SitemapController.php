<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tag;
use Illuminate\Http\Response;

/**
 * /sitemap.xml (with product and article cover images, for Google Images)
 * and a robots.txt that points to it. Only public, indexable pages are listed — never cart,
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
                'posts' => BlogPost::query()->with('featuredImage')->published()->latest('published_at')->get(['id', 'slug', 'updated_at']),
                'pages' => Page::query()->where('status', 'published')->get(['slug', 'updated_at']),
                'tags' => Tag::query()->whereHas('posts', fn ($query) => $query->published())->get(['slug', 'updated_at']),
                'latestUpdate' => collect([$products->max('updated_at'), BlogPost::query()->published()->max('updated_at')])->filter()->max(),
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * One group for everyone, then explicit groups for AI assistants/search
     * and AI training crawlers (config/site.php «ai_crawlers»). A crawler that
     * finds a group naming it ignores the «*» group, so every group repeats
     * the private paths. Crawlers listed as «blocked» get «Disallow: /».
     */
    public function robots(): Response
    {
        $crawlers = config('site.ai_crawlers');
        $blocked = $crawlers['blocked'];

        $lines = [
            '# پاسارگاد برس — '.route('home'),
            '# Machine-readable summary for AI assistants: '.route('llms').' and '.route('llms.full'),
            '',
            ...$this->group(['*']),
            '# AI assistants and AI search: fetch pages to answer a question',
            ...$this->group(array_values(array_diff($crawlers['answer'], $blocked))),
            '# AI model training',
            ...$this->group(array_values(array_diff($crawlers['training'], $blocked))),
        ];

        if ($blocked) {
            $lines = [...$lines, '# Blocked', ...array_map(fn (string $agent) => 'User-agent: '.$agent, $blocked), 'Disallow: /', ''];
        }

        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode('
', $lines).'
')->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * @param  list<string>  $userAgents
     * @return list<string>
     */
    private function group(array $userAgents): array
    {
        if ($userAgents === []) {
            return [];
        }

        return [
            ...array_map(fn (string $agent) => 'User-agent: '.$agent, $userAgents),
            'Allow: /',
            'Allow: /llms.txt',
            'Allow: /llms-full.txt',
            ...array_map(fn (string $path) => 'Disallow: '.$path, config('site.private_paths')),
            '',
        ];
    }
}
