<?php

namespace Tests\Feature\WordPressImport;

use App\Models\BlogPost;
use App\Models\Media;
use App\Models\MigrationMapping;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SeoRedirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyRedirectBuilderTest extends TestCase
{
    use RefreshDatabase;
    use WithFakeWordPressDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFakeWordPressDatabase();

        $category = ProductCategory::create(['name' => 'برس صنعتی', 'slug' => 'برس-صنعتی', 'is_active' => true]);
        Product::create(['sku' => 'WP-10', 'slug' => 'برس-اسب', 'name' => 'برس اسب', 'type' => 'quote', 'is_active' => true, 'primary_category_id' => $category->id]);
        $post = BlogPost::create(['title' => 'مقاله', 'slug' => 'کاربرد-برس', 'body' => 'x', 'status' => 'published', 'published_at' => now()]);
        $media = Media::create(['disk' => 'public', 'path' => 'media/wp-import/abc.jpg', 'mime_type' => 'image/jpeg', 'size' => 1]);

        // Products: Persian original (10), its Arabic translation (11), one never migrated (12).
        $this->wpPost(10, 'product', rawurlencode('برس-اسب'), 'برس اسب');
        $this->wpPost(11, 'product', rawurlencode('فرشاة-الحصان'), 'فرشاة');
        $this->wpPost(12, 'product', rawurlencode('برس-روتاری'), 'برس روتاری');
        $this->wp('icl_translations')->insert([
            ['element_id' => 10, 'element_type' => 'post_product', 'language_code' => 'fa', 'trid' => 500],
            ['element_id' => 11, 'element_type' => 'post_product', 'language_code' => 'ar', 'trid' => 500],
        ]);

        // A migrated category (term_taxonomy 1) and a newer one with a single product (2).
        $this->wp('terms')->insert([['term_id' => 1, 'name' => 'برس صنعتی', 'slug' => rawurlencode('برس-صنعتی')], ['term_id' => 2, 'name' => 'برس اسب', 'slug' => rawurlencode('برس-اسب')]]);
        $this->wp('term_taxonomy')->insert([['term_taxonomy_id' => 1, 'term_id' => 1, 'taxonomy' => 'product_cat'], ['term_taxonomy_id' => 2, 'term_id' => 2, 'taxonomy' => 'product_cat']]);
        $this->wp('term_relationships')->insert([['object_id' => 10, 'term_taxonomy_id' => 1], ['object_id' => 10, 'term_taxonomy_id' => 2]]);
        MigrationMapping::create(['source_type' => 'product_cat', 'source_id' => 1, 'target_type' => ProductCategory::class, 'target_id' => $category->id, 'status' => 'imported']);

        // A migrated post (20), a page (30), an image (40).
        $this->wpPost(20, 'post', rawurlencode('کاربرد-برس'), 'مقاله');
        MigrationMapping::create(['source_type' => 'post', 'source_id' => 20, 'target_type' => BlogPost::class, 'target_id' => $post->id, 'status' => 'imported']);
        $this->wpPost(30, 'page', 'about-us-2', 'درباره ما');
        $this->wpPost(31, 'page', rawurlencode('برگه-نمونه'), 'برگه نمونه');
        MigrationMapping::create(['source_type' => 'attachment', 'source_id' => 40, 'target_type' => Media::class, 'target_id' => $media->id, 'status' => 'imported']);
        $this->wp('postmeta')->insert(['post_id' => 40, 'meta_key' => '_wp_attached_file', 'meta_value' => '2020/05/horse.jpg']);

        $this->wp('options')->insert(['option_name' => 'wpseo-premium-redirects-export-plain', 'option_value' => serialize([
            'online-order-form' => ['url' => '', 'type' => 410],
        ])]);
    }

    private function wpPost(int $id, string $type, string $slug, string $title): void
    {
        $this->wp('posts')->insert(['ID' => $id, 'post_type' => $type, 'post_name' => $slug, 'post_title' => $title, 'post_status' => 'publish', 'post_date' => now()]);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->artisan('wp:import-redirects')->assertSuccessful();

        $this->assertSame(0, SeoRedirect::count());
    }

    public function test_every_old_url_kind_maps_to_its_new_address(): void
    {
        $this->artisan('wp:import-redirects', ['--commit' => true])->assertSuccessful();

        $map = SeoRedirect::pluck('to_path', 'from_path')->all();

        $this->assertSame('/products/برس-اسب', $map['/product/برس-اسب']);
        $this->assertSame('/products/برس-اسب', $map['/product/فرشاة-الحصان'], 'Arabic translation → Persian original');
        $this->assertSame('/search?q='.rawurlencode('روتاری'), $map['/product/برس-روتاری'], 'Not migrated → search');
        $this->assertSame('/categories/برس-صنعتی', $map['/product-category/برس-صنعتی']);
        $this->assertSame('/products/برس-اسب', $map['/product-category/برس-اسب'], 'Single-product category → its product');
        $this->assertSame('/blog/کاربرد-برس', $map['/کاربرد-برس']);
        $this->assertSame('/page/about-us', $map['/about-us-2']);
        $this->assertSame('/storage/media/wp-import/abc.jpg', $map['/wp-content/uploads/2020/05/horse.jpg']);
        $this->assertSame('/sitemap.xml', $map['/sitemap_index.xml']);
        $this->assertSame(410, SeoRedirect::where('from_path', '/online-order-form')->value('status_code'));
        $this->assertSame(410, SeoRedirect::where('from_path', '/برگه-نمونه')->value('status_code'));
    }

    public function test_re_running_is_idempotent_and_keeps_staff_edits(): void
    {
        SeoRedirect::create(['from_path' => '/about-us-2', 'to_path' => '/custom', 'status_code' => 301, 'source' => SeoRedirect::SOURCE_MANUAL, 'is_active' => true]);

        $this->artisan('wp:import-redirects', ['--commit' => true])->assertSuccessful();
        $count = SeoRedirect::count();
        $this->artisan('wp:import-redirects', ['--commit' => true])->assertSuccessful();

        $this->assertSame($count, SeoRedirect::count());
        $this->assertSame('/custom', SeoRedirect::where('from_path', '/about-us-2')->value('to_path'));
    }

    public function test_the_generated_redirects_work_end_to_end(): void
    {
        $this->artisan('wp:import-redirects', ['--commit' => true]);

        $this->get('/product/'.rawurlencode('فرشاة-الحصان').'/?lang=ar')
            ->assertStatus(301)
            ->assertRedirect(url('/products/'.rawurlencode('برس-اسب')));
    }
}
