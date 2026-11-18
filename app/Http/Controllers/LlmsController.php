<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Services\Seo\LlmsDocuments;
use App\Services\Seo\PostMarkdown;
use App\Services\Seo\SeoCache;
use Illuminate\Http\Response;

/**
 * Machine-readable copies of the site for AI assistants: /llms.txt,
 * /llms-full.txt (llmstxt.org) and /blog/{slug}.md. Built on request from
 * the live content and cached until the admin saves something (SeoCache).
 */
class LlmsController extends Controller
{
    private const CACHE_CONTROL = 'public, max-age=3600, stale-while-revalidate=86400';

    public function index(LlmsDocuments $documents): Response
    {
        return $this->markdown(SeoCache::remember('llms', fn () => $documents->index()));
    }

    public function full(LlmsDocuments $documents): Response
    {
        return $this->markdown(SeoCache::remember('llms-full', fn () => $documents->full()));
    }

    public function post(BlogPost $post, PostMarkdown $postMarkdown): Response
    {
        abort_unless($post->status === 'published' && $post->published_at?->lte(now()), 404);

        return $this->markdown(SeoCache::remember('post.'.$post->id, fn () => $postMarkdown->render($post)))
            ->header('Link', '<'.route('blog.show', $post->slug).'>; rel="canonical"');
    }

    private function markdown(string $content): Response
    {
        return response($content)
            ->header('Content-Type', 'text/markdown; charset=utf-8')
            ->header('Cache-Control', self::CACHE_CONTROL);
    }
}
