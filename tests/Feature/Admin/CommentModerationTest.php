<?php

namespace Tests\Feature\Admin;

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentModerationTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function pendingComment(): Comment
    {
        $post = BlogPost::create(['title' => 'مقاله', 'slug' => 'article', 'body' => 'متن', 'status' => 'published']);

        return $post->comments()->create([
            'author_name' => 'علی', 'author_email' => 'ali@example.com', 'body' => 'دیدگاه', 'status' => 'pending',
        ]);
    }

    public function test_staff_can_approve_a_pending_comment(): void
    {
        $comment = $this->pendingComment();

        $this->actingAs($this->staff())
            ->post(route('admin.comments.approve', $comment))
            ->assertRedirect();

        $this->assertSame('approved', $comment->refresh()->status);
    }

    public function test_staff_can_mark_a_comment_as_spam(): void
    {
        $comment = $this->pendingComment();

        $this->actingAs($this->staff())
            ->post(route('admin.comments.spam', $comment))
            ->assertRedirect();

        $this->assertSame('spam', $comment->refresh()->status);
    }

    public function test_staff_can_delete_a_comment(): void
    {
        $comment = $this->pendingComment();

        $this->actingAs($this->staff())
            ->delete(route('admin.comments.destroy', $comment))
            ->assertRedirect();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_guests_cannot_moderate_comments(): void
    {
        $comment = $this->pendingComment();

        $this->post(route('admin.comments.approve', $comment))->assertRedirect(route('admin.login'));
    }
}
