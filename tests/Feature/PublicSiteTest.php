<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Product;
use App\Models\ProductCategory;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_homepage_shows_active_categories_and_products(): void
    {
        $category = ProductCategory::create(['name' => 'برس صنعتی', 'slug' => 'brush', 'is_active' => true]);
        Product::create([
            'sku' => 'SKU-1', 'slug' => 'p1', 'name' => 'محصول یک', 'type' => 'quote',
            'primary_category_id' => $category->id, 'is_active' => true, 'published_at' => now(),
        ]);
        Product::create([
            'sku' => 'SKU-2', 'slug' => 'p2', 'name' => 'محصول غیرفعال', 'type' => 'quote',
            'primary_category_id' => $category->id, 'is_active' => false,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('برس صنعتی');
        $response->assertSee('محصول یک');
        $response->assertDontSee('محصول غیرفعال');
    }

    public function test_category_page_lists_its_products_and_rejects_inactive_category(): void
    {
        $category = ProductCategory::create(['name' => 'برس صنعتی', 'slug' => 'brush', 'is_active' => true]);
        $product = Product::create([
            'sku' => 'SKU-1', 'slug' => 'p1', 'name' => 'محصول یک', 'type' => 'quote',
            'primary_category_id' => $category->id, 'is_active' => true,
        ]);

        $this->get(route('categories.show', $category))->assertOk()->assertSee($product->name);

        $inactiveCategory = ProductCategory::create(['name' => 'غیرفعال', 'slug' => 'inactive', 'is_active' => false]);
        $this->get(route('categories.show', $inactiveCategory))->assertNotFound();
    }

    public function test_product_page_shows_quote_cta_for_quote_products(): void
    {
        $product = Product::create([
            'sku' => 'SKU-1', 'slug' => 'p1', 'name' => 'محصول استعلامی', 'type' => 'quote', 'is_active' => true,
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('درخواست قیمت و تماس');
    }

    public function test_inactive_product_returns_404(): void
    {
        $product = Product::create([
            'sku' => 'SKU-1', 'slug' => 'p1', 'name' => 'محصول', 'type' => 'quote', 'is_active' => false,
        ]);

        $this->get(route('products.show', $product))->assertNotFound();
    }

    public function test_blog_index_shows_only_published_posts(): void
    {
        $published = BlogPost::create(['title' => 'منتشرشده', 'slug' => 'published', 'body' => 'متن', 'status' => 'published', 'published_at' => now()]);
        BlogPost::create(['title' => 'پیش‌نویس', 'slug' => 'draft', 'body' => 'متن', 'status' => 'draft']);

        $response = $this->get(route('blog.index'));

        $response->assertOk();
        $response->assertSee($published->title);
        $response->assertDontSee('پیش‌نویس');
    }

    public function test_draft_blog_post_returns_404(): void
    {
        $post = BlogPost::create(['title' => 'پیش‌نویس', 'slug' => 'draft', 'body' => 'متن', 'status' => 'draft']);

        $this->get(route('blog.show', $post))->assertNotFound();
    }

    public function test_published_blog_post_shows_related_posts_from_same_category(): void
    {
        $category = BlogCategory::create(['name' => 'اخبار', 'slug' => 'news']);
        $post = BlogPost::create([
            'title' => 'مقاله اول', 'slug' => 'first', 'body' => 'متن', 'status' => 'published',
            'published_at' => now(), 'blog_category_id' => $category->id,
        ]);
        $related = BlogPost::create([
            'title' => 'مقاله دوم', 'slug' => 'second', 'body' => 'متن', 'status' => 'published',
            'published_at' => now(), 'blog_category_id' => $category->id,
        ]);

        $this->get(route('blog.show', $post))->assertOk()->assertSee($related->title);
    }

    public function test_footer_shows_real_contact_info_on_every_page(): void
    {
        $this->get(route('home'))
            ->assertSee('09174287445')
            ->assertSee('pbc_info@yahoo.com');
    }
}
