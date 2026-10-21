<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_cannot_manage_pages(): void
    {
        $this->get(route('admin.pages.index'))->assertRedirect(route('admin.login'));
    }

    public function test_staff_can_create_a_published_page_with_auto_slug(): void
    {
        $this->actingAs($this->staff())->post(route('admin.pages.store'), [
            'title' => 'درباره ما',
            'template' => 'about',
            'body' => '<p>متن درباره ما</p>',
            'status' => 'published',
        ])->assertRedirect(route('admin.pages.index'));

        $page = Page::first();
        $this->assertSame('درباره-ما', $page->slug);
        $this->assertNotNull($page->published_at);
    }

    public function test_draft_page_has_no_published_at(): void
    {
        $this->actingAs($this->staff())->post(route('admin.pages.store'), [
            'title' => 'پیش‌نویس', 'template' => 'default', 'body' => 'متن', 'status' => 'draft',
        ]);

        $this->assertNull(Page::first()->published_at);
    }

    public function test_slug_must_be_unique(): void
    {
        Page::create(['title' => 'صفحه', 'slug' => 'about-us', 'body' => 'متن', 'status' => 'draft']);

        $this->actingAs($this->staff())->post(route('admin.pages.store'), [
            'title' => 'صفحه دیگر', 'slug' => 'about-us', 'template' => 'default', 'body' => 'متن', 'status' => 'draft',
        ])->assertSessionHasErrors('slug');
    }

    public function test_setting_status_to_draft_clears_published_at(): void
    {
        $page = Page::create([
            'title' => 'صفحه', 'slug' => 'p', 'body' => 'متن', 'status' => 'published', 'published_at' => now(),
        ]);

        $this->actingAs($this->staff())->put(route('admin.pages.update', $page), [
            'title' => 'صفحه', 'template' => 'default', 'body' => 'متن', 'status' => 'draft',
        ]);

        $this->assertNull($page->refresh()->published_at);
    }

    public function test_staff_can_delete_a_page(): void
    {
        $page = Page::create(['title' => 'صفحه', 'slug' => 'p', 'body' => 'متن', 'status' => 'draft']);

        $this->actingAs($this->staff())->delete(route('admin.pages.destroy', $page));

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }
}
