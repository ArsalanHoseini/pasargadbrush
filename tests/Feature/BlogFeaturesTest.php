<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_reading_time_is_calculated_from_body_word_count_on_save(): void
    {
        $post = BlogPost::create([
            'title' => 'مقاله', 'slug' => 'article', 'status' => 'draft',
            'body' => implode(' ', array_fill(0, 300, 'کلمه')),
        ]);

        $this->assertSame(2, $post->reading_time);
    }

    public function test_viewing_a_published_post_increments_its_view_count(): void
    {
        $post = BlogPost::create([
            'title' => 'مقاله', 'slug' => 'article', 'body' => 'متن', 'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('blog.show', $post));
        $this->get(route('blog.show', $post));

        $this->assertSame(2, $post->refresh()->view_count);
    }

    public function test_a_post_scheduled_for_the_future_is_not_publicly_visible(): void
    {
        $post = BlogPost::create([
            'title' => 'مقاله آینده', 'slug' => 'future-article', 'body' => 'متن', 'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('blog.show', $post))->assertNotFound();
        $this->get(route('blog.index'))->assertDontSee('مقاله آینده');
    }

    public function test_the_featured_post_is_shown_separately_and_excluded_from_the_regular_grid(): void
    {
        $featured = BlogPost::create([
            'title' => 'مقاله ویژه', 'slug' => 'featured-article', 'body' => 'متن', 'status' => 'published',
            'featured' => true, 'published_at' => now()->subDay(),
        ]);
        $regular = BlogPost::create([
            'title' => 'مقاله عادی', 'slug' => 'regular-article', 'body' => 'متن', 'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('blog.index'));

        $response->assertViewHas('featured', fn ($value) => $value->is($featured));
        $response->assertViewHas('posts', fn ($posts) => ! $posts->contains($featured) && $posts->contains($regular));
    }

    public function test_the_tag_archive_page_lists_only_posts_with_that_tag(): void
    {
        $tag = Tag::create(['name' => 'نگهداری', 'slug' => 'نگهداری']);
        $tagged = BlogPost::create([
            'title' => 'مقاله با برچسب', 'slug' => 'tagged-article', 'body' => 'متن', 'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        $tagged->tags()->attach($tag);
        BlogPost::create([
            'title' => 'مقاله بی‌برچسب', 'slug' => 'untagged-article', 'body' => 'متن', 'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('blog.tag', $tag));

        $response->assertSee('مقاله با برچسب');
        $response->assertDontSee('مقاله بی‌برچسب');
    }

    public function test_staff_can_assign_comma_separated_tags_when_saving_a_post(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.blog-posts.store'), [
            'title' => 'مقاله با برچسب‌ها',
            'body' => 'متن مقاله',
            'status' => 'draft',
            'tags' => 'نگهداری، تعمیرات,کیفیت',
        ])->assertRedirect();

        $post = BlogPost::first();
        $this->assertSame(['نگهداری', 'تعمیرات', 'کیفیت'], $post->tags->pluck('name')->all());
    }

    public function test_the_rss_feed_includes_published_posts(): void
    {
        BlogPost::create([
            'title' => 'مقاله فید', 'slug' => 'feed-article', 'body' => 'متن', 'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('blog.feed'));

        $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
        $response->assertSee('مقاله فید');
    }
}
