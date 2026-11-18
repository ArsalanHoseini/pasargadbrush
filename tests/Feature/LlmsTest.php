<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LlmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create(['key' => 'contact_mobile', 'value' => '09122787949']);
        Setting::create(['key' => 'contact_email', 'value' => 'pbc_info@yahoo.com']);
    }

    private function publishedPost(array $attributes = []): BlogPost
    {
        return BlogPost::create([
            'title' => 'استریپ براش چیست؟',
            'slug' => 'استریپ-براش-چیست؟',
            'body' => '<h2>مقدمه</h2><p>متن <a href="/products/disc">برس دیسکی</a></p><ul><li><p>نایلون</p></li></ul>',
            'status' => 'published',
            'published_at' => now()->subDay(),
            ...$attributes,
        ]);
    }

    public function test_llms_txt_follows_the_llmstxt_format_with_key_facts_and_live_catalog(): void
    {
        $category = ProductCategory::create(['name' => 'برس صنعتی', 'slug' => 'industrial', 'is_active' => true]);
        $priced = Product::create(['sku' => 'P1', 'slug' => 'horse', 'name' => 'برس اسب', 'type' => 'priced', 'is_active' => true, 'primary_category_id' => $category->id]);
        $priced->variants()->create(['sku' => 'P1-1', 'label' => '۱۱×۲۲', 'price_rial' => 24000000]);
        Product::create(['sku' => 'P2', 'slug' => 'disc', 'name' => 'برس دیسکی', 'type' => 'quote', 'is_active' => true, 'primary_category_id' => $category->id]);
        Product::create(['sku' => 'P3', 'slug' => 'gone', 'name' => 'محصول خاموش', 'type' => 'quote', 'is_active' => false]);
        $post = $this->publishedPost();
        BlogPost::create(['title' => 'پیش‌نویس', 'slug' => 'draft', 'body' => 'x', 'status' => 'draft']);

        $response = $this->get('/llms.txt');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
            ->assertHeader('Cache-Control', 'max-age=3600, public, stale-while-revalidate=86400');

        $text = $response->getContent();
        $this->assertStringStartsWith("# پاسارگاد برس (Pasargad Brush)\n\n> ", $text);
        $this->assertStringContainsString('## اطلاعات کلیدی برای پاسخ دقیق', $text);
        $this->assertStringContainsString('- **تلفن و واتساپ:** ۰۹۱۲۲۷۸۷۹۴۹', $text);
        $this->assertStringContainsString('- [برس صنعتی]('.route('categories.show', 'industrial').')', $text);
        $this->assertStringContainsString('- [برس اسب]('.route('products.show', 'horse').'): قیمت ۲٬۴۰۰٬۰۰۰ تومان', $text);
        $this->assertStringContainsString('- [برس دیسکی]('.route('products.show', 'disc').'): سفارشی — قیمت پس از ثبت ابعاد اعلام می‌شود', $text);
        $this->assertStringContainsString('- [استریپ براش چیست؟]('.route('blog.markdown', $post->slug).'): ', $text);
        $this->assertStringContainsString("## Optional\n\n- [متن کامل سایت در یک فایل](".route('llms.full').')', $text);
        $this->assertStringNotContainsString('محصول خاموش', $text);
        $this->assertStringNotContainsString('پیش‌نویس', $text);
    }

    public function test_llms_full_txt_carries_price_table_contact_and_full_articles(): void
    {
        $priced = Product::create(['sku' => 'P1', 'slug' => 'horse', 'name' => 'برس اسب', 'type' => 'priced', 'is_active' => true]);
        $priced->variants()->create(['sku' => 'P1-1', 'label' => '۱۱×۲۲', 'price_rial' => 24000000]);
        $this->publishedPost(['blog_category_id' => BlogCategory::create(['name' => 'کاربرد برس ها', 'slug' => 'uses'])->id]);

        $text = $this->get('/llms-full.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
            ->getContent();

        $this->assertStringContainsString('## راهنما برای مدل‌های زبانی', $text);
        $this->assertStringContainsString('| [برس اسب]('.route('products.show', 'horse').') | ۱۱×۲۲ | ۲٬۴۰۰٬۰۰۰ |', $text);
        $this->assertStringContainsString('## مراحل خرید', $text);
        $this->assertStringContainsString('- ایمیل: pbc_info@yahoo.com', $text);
        $this->assertStringContainsString("### استریپ براش چیست؟\n\n- نشانی: ".route('blog.show', 'استریپ-براش-چیست؟'), $text);
        $this->assertStringContainsString('- دسته: کاربرد برس ها', $text);
        // The article's own <h2> sits under its «###» title.
        $this->assertStringContainsString("#### مقدمه\n\nمتن [برس دیسکی](".url('/products/disc').")\n\n- نایلون", $text);
    }

    public function test_llms_full_txt_caps_the_number_of_articles(): void
    {
        config(['site.llms_full_post_limit' => 2]);

        foreach (range(1, 3) as $number) {
            BlogPost::create(['title' => "مقاله {$number}", 'slug' => "post-{$number}", 'body' => 'متن', 'status' => 'published', 'published_at' => now()->subDays($number)]);
        }

        $text = $this->get('/llms-full.txt')->getContent();

        $this->assertStringContainsString('### مقاله 1', $text);
        $this->assertStringContainsString('### مقاله 2', $text);
        $this->assertStringNotContainsString('### مقاله 3', $text);
    }

    public function test_saving_content_in_the_admin_refreshes_the_cached_files(): void
    {
        $this->get('/llms.txt')->assertDontSee('برس تازه');

        Product::create(['sku' => 'P9', 'slug' => 'fresh', 'name' => 'برس تازه', 'type' => 'quote', 'is_active' => true]);

        $this->get('/llms.txt')->assertSee('برس تازه');
    }

    public function test_article_markdown_copy_has_frontmatter_body_and_canonical_link(): void
    {
        $post = $this->publishedPost([
            'blog_category_id' => BlogCategory::create(['name' => 'کاربرد برس ها', 'slug' => 'uses'])->id,
        ]);
        $post->tags()->attach(Tag::create(['name' => 'برس نواری', 'slug' => 'strip'])->id);

        $response = $this->get(route('blog.markdown', $post->slug));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
            ->assertHeader('Link', '<'.route('blog.show', $post->slug).'>; rel="canonical"');

        $markdown = $response->getContent();
        $this->assertStringStartsWith("---\ntitle: \"استریپ براش چیست؟\"\n", $markdown);
        $this->assertStringContainsString('url: "'.route('blog.show', $post->slug).'"', $markdown);
        $this->assertStringContainsString('date_published: "'.$post->published_at->toIso8601String().'"', $markdown);
        $this->assertStringContainsString('author: "شرکت برس صنعت پاسارگاد"', $markdown);
        $this->assertStringContainsString('category: "کاربرد برس ها"', $markdown);
        $this->assertStringContainsString("tags:\n  - \"برس نواری\"", $markdown);
        $this->assertStringContainsString("---\n\n# استریپ براش چیست؟\n\n## مقدمه\n\nمتن [برس دیسکی](".url('/products/disc').')', $markdown);
    }

    public function test_markdown_copy_is_404_for_missing_draft_and_scheduled_articles(): void
    {
        $this->publishedPost(['slug' => 'draft-post', 'status' => 'draft']);
        $this->publishedPost(['slug' => 'scheduled-post', 'published_at' => now()->addDay()]);

        $this->get('/blog/no-such-post.md')->assertNotFound();
        $this->get('/blog/draft-post.md')->assertNotFound();
        $this->get('/blog/scheduled-post.md')->assertNotFound();
    }

    public function test_every_public_page_links_both_llms_files_and_an_article_links_its_markdown_copy(): void
    {
        $post = $this->publishedPost();

        $this->get('/')
            ->assertSee('<link rel="alternate" type="text/markdown" href="'.route('llms').'" title="llms.txt">', false)
            ->assertSee('<link rel="alternate" type="text/markdown" href="'.route('llms.full').'" title="llms-full.txt">', false);

        $this->get(route('blog.show', $post->slug))
            ->assertSee('<link rel="alternate" type="text/markdown" href="'.route('blog.markdown', $post->slug).'"', false);
    }
}
