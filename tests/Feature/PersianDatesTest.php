<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\Customer;
use App\Models\QuoteRequest;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PersianDatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Carbon::setTestNow('2026-11-01 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function blogPost(): BlogPost
    {
        return BlogPost::create([
            'title' => 'کاربرد برس صنعتی', 'slug' => 'brush-uses', 'body' => '<p>متن</p>',
            'status' => 'published', 'published_at' => '2026-10-31 09:15:00', 'reading_time' => 3,
        ]);
    }

    public function test_the_app_runs_on_tehran_time(): void
    {
        $this->assertSame('Asia/Tehran', config('app.timezone'));
    }

    public function test_blog_dates_are_shown_in_the_persian_calendar(): void
    {
        $post = $this->blogPost();
        Comment::create(['blog_post_id' => $post->id, 'author_name' => 'علی', 'author_email' => 'a@example.com', 'body' => 'عالی', 'status' => 'approved']);

        // The datetime attribute keeps ISO (Gregorian) for machines; only the visible text is Persian.
        $this->get(route('blog.index'))
            ->assertSee('">۹ آبان ۱۴۰۵</time>', false)
            ->assertDontSee('>2026-10-31', false);

        $this->get(route('blog.show', $post))
            ->assertSee('<time datetime="'.$post->published_at->toIso8601String().'">۹ آبان ۱۴۰۵</time>', false)
            ->assertSee('۱۰ آبان ۱۴۰۵');
    }

    public function test_a_typed_publish_time_is_kept_as_typed(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);

        $this->actingAs($staff)->post(route('admin.blog-posts.store'), [
            'title' => 'زمان‌بندی', 'body' => '<p>متن</p>', 'status' => 'published', 'published_at' => '2026-11-05T10:00',
        ])->assertSessionHasNoErrors();

        $post = BlogPost::firstOrFail();
        $this->assertSame('10:00', $post->published_at->format('H:i'));
        $this->assertSame('۱۴ آبان ۱۴۰۵، ۱۰:۰۰', persian_date($post->published_at));

        $this->actingAs($staff)->get(route('admin.blog-posts.edit', $post))->assertSee('به تاریخ شمسی');
    }

    public function test_dashboard_tables_use_compact_persian_dates(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);
        $this->blogPost();
        QuoteRequest::create(['name' => 'علی', 'phone' => '09120000000', 'message' => 'سلام']);
        Customer::create(['name' => 'علی', 'phone' => '09121234567'])->orders()->create([
            'order_number' => 'PB-1', 'status' => 'pending', 'payment_status' => 'unpaid',
            'subtotal_rial' => 1, 'total_rial' => 1, 'placed_at' => now(),
        ]);

        $this->actingAs($staff)->get(route('admin.blog-posts.index'))->assertSee('۱۴۰۵/۰۸/۰۹')->assertDontSee('2026-10-31');
        $this->actingAs($staff)->get(route('admin.orders.index'))->assertSee('۱۴۰۵/۰۸/۱۰ ۱۲:۰۰');
        $this->actingAs($staff)->get(route('admin.quote-requests.index'))->assertSee('۱۴۰۵/۰۸/۱۰ ۱۲:۰۰');
        $this->actingAs($staff)->get(route('admin.dashboard'))->assertSee('۱۴۰۵/۰۸/۱۰ ۱۲:۰۰');
    }

    public function test_footer_shows_the_persian_year(): void
    {
        $this->get(route('home'))->assertSee('© ۱۴۰۵');
    }

    public function test_machine_readable_dates_stay_gregorian(): void
    {
        $this->blogPost();

        $this->get(route('blog.feed'))->assertSee('Sat, 31 Oct 2026 09:15:00 +0330');
        $this->get(route('sitemap'))->assertSee('2026-');
    }
}
