<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Services\Seo\SchemaGraph;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StructuredDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create(['key' => 'contact_mobile', 'value' => '09122787949']);
        Setting::create(['key' => 'contact_email', 'value' => 'pbc_info@yahoo.com']);
        Setting::create(['key' => 'social_instagram_url', 'value' => 'https://www.instagram.com/pasargad_brush_co']);
    }

    /**
     * The page's one JSON-LD graph, nodes keyed by «@type» (merge-only nodes under «_merge»).
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function graph(TestResponse $response): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $scripts);
        $this->assertCount(1, $scripts[1], 'Exactly one JSON-LD script per page.');

        $data = json_decode($scripts[1][0], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('https://schema.org', $data['@context']);

        $nodes = [];

        foreach ($data['@graph'] as $node) {
            $nodes[$node['@type'] ?? '_merge'][] = $node;
        }

        return $nodes;
    }

    public function test_homepage_graph_has_organization_website_webpage_and_catalog(): void
    {
        $category = ProductCategory::create(['name' => 'برس صنعتی', 'slug' => 'industrial', 'is_active' => true]);
        Product::create(['sku' => 'P1', 'slug' => 'disc', 'name' => 'برس دیسکی', 'type' => 'quote', 'is_active' => true, 'primary_category_id' => $category->id]);

        $graph = $this->graph($this->get('/')->assertOk());
        $organizationId = url('/').'/#organization';

        $organization = $graph['Organization'][0];
        $this->assertSame($organizationId, $organization['@id']);
        $this->assertSame('شرکت برس صنعت پاسارگاد', $organization['legalName']);
        $this->assertSame('+989122787949', $organization['telephone']);
        $this->assertSame('sales', $organization['contactPoint'][0]['contactType']);
        $this->assertSame(['https://www.instagram.com/pasargad_brush_co'], $organization['sameAs']);
        $this->assertSame('IR', $organization['address']['addressCountry']);

        $this->assertSame(['@id' => $organizationId], $graph['WebSite'][0]['publisher']);
        $this->assertSame(['@id' => $organizationId], $graph['WebPage'][0]['mainEntity']);
        $this->assertSame($organizationId, $graph['_merge'][0]['@id']);
        $this->assertSame('برس صنعتی', $graph['_merge'][0]['hasOfferCatalog']['itemListElement'][0]['name']);
    }

    public function test_article_graph_has_blog_posting_breadcrumbs_and_markdown_encoding(): void
    {
        $post = BlogPost::create([
            'title' => 'استریپ براش چیست؟', 'slug' => 'استریپ-براش-چیست؟', 'status' => 'published',
            'body' => '<p>'.implode(' ', array_fill(0, 300, 'کلمه')).'</p>', 'published_at' => now()->subDay(),
            'blog_category_id' => BlogCategory::create(['name' => 'کاربرد برس ها', 'slug' => 'uses'])->id,
        ]);
        $url = route('blog.show', $post->slug);

        $response = $this->get($url)->assertOk();
        $graph = $this->graph($response);

        $article = $graph['BlogPosting'][0];
        $this->assertSame($url.'#article', $article['@id']);
        $this->assertSame('استریپ براش چیست؟', $article['headline']);
        $this->assertSame($post->published_at->toIso8601String(), $article['datePublished']);
        $this->assertSame(['@id' => url('/').'/#organization'], $article['author']);
        $this->assertSame(['@id' => url('/').'/#organization'], $article['publisher']);
        $this->assertSame(['@id' => $url.'#webpage'], $article['mainEntityOfPage']);
        $this->assertSame('کاربرد برس ها', $article['articleSection']);
        $this->assertSame(300, $article['wordCount']);
        $this->assertSame('PT2M', $article['timeRequired']);
        $this->assertSame(['@type' => 'MediaObject', 'contentUrl' => route('blog.markdown', $post->slug), 'encodingFormat' => 'text/markdown'], $article['encoding']);
        $this->assertSame(['صفحه اصلی', 'مقالات', 'استریپ براش چیست؟'], array_column($graph['BreadcrumbList'][0]['itemListElement'], 'name'));
        $this->assertCount(1, $graph['Organization'], 'The Organization is defined once and referenced by @id.');

        $response
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('<meta property="article:published_time" content="'.$post->published_at->toIso8601String().'">', false)
            ->assertSee('<link rel="canonical" href="'.$url.'">', false)
            ->assertSee('<title>استریپ براش چیست؟ — پاسارگاد برس</title>', false);
    }

    public function test_fixed_price_products_get_rial_offers_and_quote_products_get_none(): void
    {
        $priced = Product::create(['sku' => 'P1', 'slug' => 'horse', 'name' => 'برس اسب', 'type' => 'priced', 'is_active' => true]);
        $priced->variants()->create(['sku' => 'P1-1', 'label' => 'کوچک', 'price_rial' => 24000000]);
        $priced->variants()->create(['sku' => 'P1-2', 'label' => 'بزرگ', 'price_rial' => 30000000]);
        Product::create(['sku' => 'P2', 'slug' => 'disc', 'name' => 'برس دیسکی', 'type' => 'quote', 'is_active' => true]);

        $offers = $this->graph($this->get('/products/horse'))['Product'][0]['offers'];
        $this->assertSame('AggregateOffer', $offers['@type']);
        $this->assertSame('IRR', $offers['priceCurrency']);
        $this->assertSame([24000000, 30000000, 2], [$offers['lowPrice'], $offers['highPrice'], $offers['offerCount']]);

        $this->assertArrayNotHasKey('offers', $this->graph($this->get('/products/disc'))['Product'][0]);
    }

    public function test_about_and_contact_pages_get_their_page_types(): void
    {
        Page::create(['title' => 'درباره ما', 'slug' => 'about-us', 'body' => '<p>متن</p>', 'status' => 'published']);
        Page::create(['title' => 'تماس با ما', 'slug' => 'contact-us', 'body' => '<p>متن</p>', 'status' => 'published', 'template' => 'contact']);

        $this->assertArrayHasKey('AboutPage', $this->graph($this->get('/page/about-us')));
        $this->assertArrayHasKey('ContactPage', $this->graph($this->get('/page/contact-us')));
    }

    public function test_json_ld_cannot_close_its_script_tag(): void
    {
        $html = app(SchemaGraph::class)->render(url('/x'), 'عنوان </script><script>alert(1)</script>', null);

        $this->assertSame(1, substr_count($html, '</script>'));
        // «<» is written as its JSON \u escape (JSON_HEX_TAG).
        $this->assertStringContainsString(chr(92).'u003C/script', $html);
    }

    public function test_titles_never_repeat_the_site_name(): void
    {
        Page::create(['title' => 'درباره پاسارگاد برس', 'slug' => 'about-us', 'body' => '<p>متن</p>', 'status' => 'published']);

        $this->get('/page/about-us')->assertSee('<title>درباره پاسارگاد برس</title>', false);
    }
}
