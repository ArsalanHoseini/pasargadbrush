<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogCommentTest extends TestCase
{
    use RefreshDatabase;

    private function publishedPost(): BlogPost
    {
        return BlogPost::create([
            'title' => 'مقاله', 'slug' => 'article', 'body' => 'متن', 'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
    }

    public function test_a_visitor_can_submit_a_comment_and_it_starts_as_pending(): void
    {
        $post = $this->publishedPost();

        $this->post(route('blog.comments.store', $post), [
            'author_name' => 'علی',
            'author_email' => 'ali@example.com',
            'body' => 'دیدگاه تست',
        ])->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'blog_post_id' => $post->id,
            'author_name' => 'علی',
            'status' => 'pending',
        ]);
    }

    public function test_a_pending_comment_is_not_shown_on_the_public_post_page(): void
    {
        $post = $this->publishedPost();
        $post->comments()->create([
            'author_name' => 'علی', 'author_email' => 'ali@example.com', 'body' => 'دیدگاه در انتظار', 'status' => 'pending',
        ]);

        $this->get(route('blog.show', $post))->assertDontSee('دیدگاه در انتظار');
    }

    public function test_an_approved_comment_is_shown_on_the_public_post_page(): void
    {
        $post = $this->publishedPost();
        $post->comments()->create([
            'author_name' => 'علی', 'author_email' => 'ali@example.com', 'body' => 'دیدگاه تأییدشده', 'status' => 'approved',
        ]);

        $this->get(route('blog.show', $post))->assertSee('دیدگاه تأییدشده');
    }

    public function test_filling_the_honeypot_field_blocks_the_comment(): void
    {
        $post = $this->publishedPost();

        $this->post(route('blog.comments.store', $post), [
            'author_name' => 'بات',
            'author_email' => 'bot@example.com',
            'body' => 'اسپم',
            'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertDatabaseMissing('comments', ['author_name' => 'بات']);
    }
}
