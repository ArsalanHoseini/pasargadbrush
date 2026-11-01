<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SeoRedirect;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    private function redirect(string $from, ?string $to, int $code = 301, bool $active = true): SeoRedirect
    {
        return SeoRedirect::create([
            'from_path' => SeoRedirect::normalizePath($from), 'to_path' => $to, 'status_code' => $code,
            'source' => SeoRedirect::SOURCE_WORDPRESS, 'is_active' => $active,
        ]);
    }

    public function test_an_old_url_permanently_redirects_to_its_new_address(): void
    {
        $redirect = $this->redirect('/product/برس-اسب', '/products/برس-اسب');

        $this->get('/product/'.rawurlencode('برس-اسب').'/')
            ->assertStatus(301)
            ->assertRedirect(url('/products/'.rawurlencode('برس-اسب')));

        $this->assertSame(1, $redirect->refresh()->hits_count);
    }

    public function test_raw_unicode_paths_trailing_slashes_and_wpml_language_parameters_all_match(): void
    {
        $this->redirect('/product/برس-اسب', '/products/برس-اسب');

        $this->get('/product/برس-اسب')->assertStatus(301);
        $this->get('/product/'.rawurlencode('برس-اسب').'/?lang=en')->assertStatus(301);
    }

    public function test_date_prefixed_post_urls_and_resized_images_resolve_to_their_original(): void
    {
        $this->redirect('/my-old-post', '/blog/my-old-post');
        $this->redirect('/wp-content/uploads/2020/05/brush.jpg', '/storage/media/wp-import/abc.jpg');

        $this->get('/2013/04/02/my-old-post/')->assertRedirect(url('/blog/my-old-post'));
        $this->get('/wp-content/uploads/2020/05/brush-300x200.jpg')->assertRedirect(url('/storage/media/wp-import/abc.jpg'));
    }

    public function test_a_removed_page_answers_410_gone_in_persian(): void
    {
        $this->redirect('/online-order-form', null, 410);

        $this->get('/online-order-form/')
            ->assertStatus(410)
            ->assertSee('این صفحه حذف شده است');
    }

    public function test_unknown_urls_get_a_persian_404_page(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSee('صفحه پیدا نشد')
            ->assertSee('noindex', false);
    }

    public function test_a_real_page_is_never_shadowed_by_a_redirect(): void
    {
        $this->redirect('/blog', '/somewhere-else');

        $this->get('/blog')->assertOk();
    }

    public function test_inactive_redirects_posts_and_admin_urls_are_left_alone(): void
    {
        $this->redirect('/inactive', '/blog', active: false);
        $this->redirect('/old-form', '/blog');
        $this->redirect('/admin/old', '/blog');

        $this->get('/inactive')->assertNotFound();
        $this->post('/old-form')->assertNotFound();
        $this->get('/admin/old')->assertNotFound();
    }

    public function test_renaming_a_product_redirects_its_old_url_without_chains(): void
    {
        $product = Product::create(['sku' => 'P1', 'slug' => 'نام-اول', 'name' => 'برس', 'type' => 'quote', 'is_active' => true]);
        $this->redirect('/product/نام-وردپرسی', '/products/نام-اول');

        $product->update(['slug' => 'نام-دوم']);
        $product->update(['slug' => 'نام-سوم']);

        $this->get('/products/نام-اول')->assertRedirect(url('/products/'.rawurlencode('نام-سوم')));
        $this->get('/products/نام-دوم')->assertRedirect(url('/products/'.rawurlencode('نام-سوم')));
        $this->get('/product/نام-وردپرسی')->assertRedirect(url('/products/'.rawurlencode('نام-سوم')));
        $this->get('/products/نام-سوم')->assertOk();
        $this->assertSame(SeoRedirect::SOURCE_AUTO, SeoRedirect::where('from_path', '/products/نام-اول')->value('source'));
    }

    public function test_renaming_categories_posts_and_pages_also_records_redirects(): void
    {
        ProductCategory::create(['name' => 'دسته', 'slug' => 'old-cat', 'is_active' => true])->update(['slug' => 'new-cat']);
        BlogPost::create(['title' => 'مقاله', 'slug' => 'old-post', 'body' => 'x', 'status' => 'draft'])->update(['slug' => 'new-post']);
        Page::create(['title' => 'صفحه', 'slug' => 'old-page', 'body' => 'x', 'status' => 'draft'])->update(['slug' => 'new-page']);

        $this->assertSame([
            '/blog/old-post' => '/blog/new-post',
            '/categories/old-cat' => '/categories/new-cat',
            '/page/old-page' => '/page/new-page',
        ], SeoRedirect::orderBy('from_path')->pluck('to_path', 'from_path')->all());
    }

    public function test_search_matches_every_word_ignoring_stop_words_and_arabic_letter_forms(): void
    {
        Product::create(['sku' => 'P1', 'slug' => 'p1', 'name' => 'برس قاليشويی صنعتی', 'type' => 'quote', 'is_active' => true, 'description' => 'برای شستشو']);
        Product::create(['sku' => 'P2', 'slug' => 'p2', 'name' => 'برس مبل شو', 'type' => 'quote', 'is_active' => true]);

        $this->get('/search?q='.urlencode('قالیشویی و شستشو'))->assertSee('برس قاليشويی صنعتی')->assertDontSee('برس مبل شو');
        $this->get('/search?q='.urlencode('مبل فولادی'))->assertSee('برس مبل شو');
    }
}
