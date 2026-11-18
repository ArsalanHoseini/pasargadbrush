<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Header, footer, homepage strip and site-wide SEO plumbing from the owner's
 * correction list of 2026-11-07.
 */
class SiteChromeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_footer_shows_the_new_contact_details_socials_and_clickable_map(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('tel:09122787949', false)
            ->assertSee('https://wa.me/989122787949', false)
            ->assertSee('بله:', false)
            ->assertSee('09174287445')
            ->assertSee('https://www.instagram.com/pasargad_brush_co', false)
            ->assertSee('src="https://www.google.com/maps/embed?', false)
            ->assertSee('href="https://maps.google.com/?cid=8140887252747672839"', false)
            ->assertSee('تخت جمشید، کیلومتر ۶۰')
            ->assertSee('شرکت برس صنعت پاسارگاد')
            ->assertDontSee('برس صنعتی پاسارگاد')
            ->assertDontSee('tel:09174287445', false);
    }

    public function test_footer_shows_the_enamad_trust_seal(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee("href='https://trustseal.enamad.ir/?id=8011554&Code=ksYMN1Y0FIDaq7iSC8d6uzQtTe4nIHqt'", false)
            ->assertSee("code='ksYMN1Y0FIDaq7iSC8d6uzQtTe4nIHqt'", false);
    }

    public function test_menus_skip_categories_without_active_products(): void
    {
        $withProducts = ProductCategory::create(['name' => 'برس ابزاری', 'slug' => 'tools', 'is_active' => true]);
        ProductCategory::create(['name' => 'فرشاة الصناعی', 'slug' => 'arabic', 'is_active' => true]);
        $onlyInactive = ProductCategory::create(['name' => 'دسته خاموش', 'slug' => 'off', 'is_active' => true]);
        Product::create(['sku' => 'P1', 'slug' => 'p1', 'name' => 'برس', 'type' => 'quote', 'is_active' => true, 'primary_category_id' => $withProducts->id]);
        Product::create(['sku' => 'P2', 'slug' => 'p2', 'name' => 'برس دو', 'type' => 'quote', 'is_active' => false, 'primary_category_id' => $onlyInactive->id]);

        $this->get(route('home'))
            ->assertSee('برس ابزاری')
            ->assertDontSee('فرشاة الصناعی')
            ->assertDontSee('دسته خاموش');
    }

    public function test_mobile_bottom_bar_offers_home_products_search_cart_and_account(): void
    {
        $this->get(route('home'))
            ->assertSee('aria-label="ناوبری موبایل"', false)
            ->assertSeeInOrder(['ناوبری موبایل', 'خانه', 'محصولات', 'جستجو', 'سبد خرید', 'ورود']);
    }

    public function test_homepage_brush_types_strip_links_only_to_live_products(): void
    {
        Product::create(['sku' => 'WP-30710', 'slug' => 'برس-سیلندری', 'name' => 'برس سیلندری', 'type' => 'quote', 'is_active' => true]);
        Product::create(['sku' => 'WP-30749', 'slug' => 'برس-دیسکی', 'name' => 'برس دیسکی', 'type' => 'quote', 'is_active' => false]);

        $this->get(route('home'))
            ->assertSee('aria-label="انواع برس"', false)
            ->assertSee(route('products.show', 'برس-سیلندری'), false)
            ->assertSee('images/brush-types/cylindrical.webp', false)
            ->assertDontSee('images/brush-types/disc.webp', false)
            ->assertSeeInOrder(['تولیدکننده و فروشنده برس و فرچه صنعتی', 'aria-label="انواع برس"', 'چرا پاسارگاد برس؟'], false);
    }

    public function test_homepage_no_longer_shows_the_key_figures_section(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('تنوع محصول')
            ->assertDontSee('قرارداد موفق')
            ->assertDontSee('data-counter', false);
    }

    public function test_www_host_is_redirected_to_the_canonical_address(): void
    {
        config(['app.url' => 'https://pasargadbrush.com']);

        $this->get('https://www.pasargadbrush.com/blog?page=2')
            ->assertStatus(301)
            ->assertRedirect('https://pasargadbrush.com/blog?page=2');

        $this->get('https://pasargadbrush.com/blog')->assertOk();
    }
}
