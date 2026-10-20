<?php

namespace Tests\Feature\Admin;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogManagementTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_staff_can_create_a_blog_category(): void
    {
        $this->actingAs($this->staff())->post(route('admin.blog-categories.store'), [
            'name' => 'اخبار',
        ])->assertRedirect(route('admin.blog-categories.index'));

        $this->assertDatabaseHas('blog_categories', ['name' => 'اخبار', 'slug' => 'اخبار']);
    }

    public function test_category_with_posts_cannot_be_deleted(): void
    {
        $category = BlogCategory::create(['name' => 'اخبار', 'slug' => 'news']);
        BlogPost::create([
            'blog_category_id' => $category->id, 'title' => 'مقاله', 'slug' => 'article', 'body' => 'متن', 'status' => 'draft',
        ]);

        $this->actingAs($this->staff())->delete(route('admin.blog-categories.destroy', $category));

        $this->assertDatabaseHas('blog_categories', ['id' => $category->id]);
    }

    public function test_staff_can_create_a_published_post_with_featured_image(): void
    {
        $category = BlogCategory::create(['name' => 'اخبار', 'slug' => 'news']);
        $media = Media::create(['disk' => 'public', 'path' => 'x.jpg', 'mime_type' => 'image/jpeg', 'size' => 10]);

        $this->actingAs($this->staff())->post(route('admin.blog-posts.store'), [
            'title' => 'مقاله تست',
            'blog_category_id' => $category->id,
            'body' => 'متن کامل مقاله',
            'status' => 'published',
            'featured_media_id' => $media->id,
        ])->assertRedirect();

        $post = BlogPost::first();
        $this->assertSame('published', $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->featuredImage->contains($media));
    }

    public function test_updating_a_published_post_does_not_reset_its_publish_date(): void
    {
        $post = BlogPost::create([
            'title' => 'مقاله', 'slug' => 'article', 'body' => 'متن', 'status' => 'published',
            'published_at' => now()->subDays(5),
        ]);
        $originalDate = $post->published_at;

        $this->actingAs($this->staff())->put(route('admin.blog-posts.update', $post), [
            'title' => 'مقاله ویرایش‌شده',
            'body' => 'متن جدید',
            'status' => 'published',
        ]);

        $this->assertTrue($post->refresh()->published_at->equalTo($originalDate));
    }

    public function test_setting_status_to_draft_clears_published_at(): void
    {
        $post = BlogPost::create([
            'title' => 'مقاله', 'slug' => 'article', 'body' => 'متن', 'status' => 'published', 'published_at' => now(),
        ]);

        $this->actingAs($this->staff())->put(route('admin.blog-posts.update', $post), [
            'title' => 'مقاله', 'body' => 'متن', 'status' => 'draft',
        ]);

        $this->assertNull($post->refresh()->published_at);
    }
}
