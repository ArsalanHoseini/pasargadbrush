<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Media;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_only_public_pages_with_product_images(): void
    {
        $category = ProductCategory::create(['name' => 'دسته', 'slug' => 'برس-صنعتی', 'is_active' => true]);
        ProductCategory::create(['name' => 'خالی', 'slug' => 'empty-cat', 'is_active' => true]);
        ProductCategory::create(['name' => 'غیرفعال', 'slug' => 'hidden-cat', 'is_active' => false]);
        $product = Product::create(['sku' => 'P1', 'slug' => 'برس-اسب', 'name' => 'برس اسب', 'type' => 'quote', 'is_active' => true, 'primary_category_id' => $category->id]);
        Product::create(['sku' => 'P2', 'slug' => 'hidden-product', 'name' => 'x', 'type' => 'quote', 'is_active' => false]);
        $image = Media::create(['disk' => 'public', 'path' => 'media/horse.jpg', 'mime_type' => 'image/jpeg', 'size' => 1]);
        $product->images()->attach($image->id, ['role' => 'gallery', 'sort_order' => 0]);
        BlogPost::create(['title' => 'مقاله', 'slug' => 'published-post', 'body' => 'x', 'status' => 'published', 'published_at' => now()->subDay()]);
        BlogPost::create(['title' => 'آینده', 'slug' => 'scheduled-post', 'body' => 'x', 'status' => 'published', 'published_at' => now()->addDay()]);
        BlogPost::create(['title' => 'پیش‌نویس', 'slug' => 'draft-post', 'body' => 'x', 'status' => 'draft']);
        Page::create(['title' => 'درباره ما', 'slug' => 'about-us', 'body' => 'x', 'status' => 'published']);

        $response = $this->get('/sitemap.xml');

        $response->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = $response->getContent();

        $this->assertNotFalse(simplexml_load_string($xml), 'The sitemap is well-formed XML.');
        $this->assertStringContainsString(route('products.show', 'برس-اسب'), $xml);
        $this->assertStringContainsString(route('categories.show', 'برس-صنعتی'), $xml);
        $this->assertStringContainsString(route('blog.show', 'published-post'), $xml);
        $this->assertStringContainsString(route('pages.show', 'about-us'), $xml);
        $this->assertStringContainsString('<image:loc>'.$image->url().'</image:loc>', $xml);
        foreach (['hidden-cat', 'empty-cat', 'hidden-product', 'scheduled-post', 'draft-post', '/cart', '/checkout', '/account'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $xml);
        }
    }

    public function test_robots_txt_points_to_the_sitemap_and_keeps_private_pages_out(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap: '.route('sitemap'), false)
            ->assertSee('Disallow: /admin', false)
            ->assertSee('Disallow: /checkout', false)
            ->assertSee('Allow: /llms.txt', false)
            ->assertSee('Allow: /llms-full.txt', false);
    }

    public function test_robots_txt_names_ai_crawlers_and_repeats_private_paths_in_every_group(): void
    {
        $robots = $this->get('/robots.txt')->getContent();
        $groups = preg_split('/
\s*
/', trim($robots));
        $agentGroups = array_values(array_filter($groups, fn (string $group) => str_contains($group, 'User-agent:')));

        $this->assertCount(3, $agentGroups, 'Everyone, AI assistants/search, AI training.');

        foreach ($agentGroups as $group) {
            foreach (config('site.private_paths') as $path) {
                $this->assertStringContainsString("Disallow: {$path}
", $group.'
');
            }
        }

        foreach (['OAI-SearchBot', 'ChatGPT-User', 'Claude-SearchBot', 'PerplexityBot', 'GPTBot', 'ClaudeBot', 'Google-Extended', 'CCBot', 'Bytespider'] as $agent) {
            $this->assertStringContainsString("User-agent: {$agent}
", $robots);
        }
    }

    public function test_a_blocked_ai_crawler_gets_disallow_all_and_leaves_its_allow_group(): void
    {
        config(['site.ai_crawlers.blocked' => ['GPTBot']]);

        $robots = $this->get('/robots.txt')->getContent();

        $this->assertSame(1, substr_count($robots, 'User-agent: GPTBot'));
        $this->assertStringContainsString('User-agent: GPTBot
Disallow: /
', $robots);
    }

    public function test_sitemap_lists_article_cover_images(): void
    {
        $post = BlogPost::create(['title' => 'مقاله', 'slug' => 'published-post', 'body' => 'x', 'status' => 'published', 'published_at' => now()->subDay()]);
        $cover = Media::create(['disk' => 'public', 'path' => 'media/cover.jpg', 'mime_type' => 'image/jpeg', 'size' => 1]);
        $post->featuredImage()->attach($cover->id, ['role' => 'featured']);

        $this->get('/sitemap.xml')->assertSee('<image:loc>'.$cover->url().'</image:loc>', false);
    }

    public function test_private_pages_are_marked_noindex(): void
    {
        $this->get('/cart')->assertSee('<meta name="robots" content="noindex, follow">', false);
        $this->get('/search?q=x')->assertSee('noindex, follow', false);
        $this->get('/login')->assertSee('noindex, follow', false);
        $this->get('/')->assertDontSee('name="robots"', false);
    }
}
