<?php

namespace Tests\Feature;

use App\Models\Page;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_published_default_page_renders_its_body(): void
    {
        $page = Page::create([
            'title' => 'درباره ما', 'slug' => 'about-us', 'body' => '<p>متن واقعی درباره ما</p>',
            'template' => 'about', 'status' => 'published', 'published_at' => now(),
        ]);

        $response = $this->get(route('pages.show', $page));

        $response->assertOk()->assertSee('متن واقعی درباره ما', false);
    }

    public function test_contact_template_shows_real_settings_and_quote_form(): void
    {
        $page = Page::create([
            'title' => 'تماس با ما', 'slug' => 'contact-us', 'body' => '<p>با ما در تماس باشید</p>',
            'template' => 'contact', 'status' => 'published', 'published_at' => now(),
        ]);

        $response = $this->get(route('pages.show', $page));

        $response->assertOk();
        $response->assertSee('09174287445');
        $response->assertSee('pbc_info@yahoo.com');
        $response->assertSee('درخواست استعلام قیمت');
    }

    public function test_draft_page_returns_404(): void
    {
        $page = Page::create(['title' => 'پیش‌نویس', 'slug' => 'draft-page', 'body' => 'متن', 'status' => 'draft']);

        $this->get(route('pages.show', $page))->assertNotFound();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/page/does-not-exist')->assertNotFound();
    }
}
