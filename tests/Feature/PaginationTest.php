<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Customer;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * Rows that share a sort value (imported posts, same-second orders) must
 * still fall on exactly one page each.
 */
class PaginationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<int>
     */
    private function idsAcrossPages(callable $fetchPage): array
    {
        $ids = [];
        $page = 1;

        do {
            /** @var LengthAwarePaginator $paginator */
            $paginator = $fetchPage($page);
            array_push($ids, ...$paginator->getCollection()->modelKeys());
            $page++;
        } while ($page <= $paginator->lastPage());

        return $ids;
    }

    public function test_blog_tag_pages_list_every_post_once_when_publish_dates_tie(): void
    {
        $tag = Tag::create(['name' => 'نگهداری', 'slug' => 'نگهداری']);
        $publishedAt = now()->subDay();

        foreach (range(1, 15) as $number) {
            BlogPost::create([
                'title' => "مقاله {$number}", 'slug' => "post-{$number}", 'body' => 'متن',
                'status' => 'published', 'published_at' => $publishedAt,
            ])->tags()->attach($tag);
        }

        $ids = $this->idsAcrossPages(fn (int $page) => $this->get(route('blog.tag', ['tag' => $tag, 'page' => $page]))
            ->assertOk()
            ->viewData('posts'));

        $this->assertCount(15, $ids);
        $this->assertSame(range(15, 1), $ids);
    }

    public function test_customer_order_pages_list_every_order_once_when_placed_at_ties(): void
    {
        $customer = Customer::create(['name' => 'علی', 'phone' => '09121234567']);
        $placedAt = now()->subHour();

        foreach (range(1, 20) as $number) {
            $customer->orders()->create([
                'order_number' => "PB-{$number}", 'status' => 'pending', 'payment_status' => 'unpaid',
                'subtotal_rial' => 10, 'total_rial' => 10, 'placed_at' => $placedAt,
            ]);
        }

        $ids = $this->idsAcrossPages(fn (int $page) => $this->actingAs($customer, 'customer')
            ->get(route('account.orders.index', ['page' => $page]))
            ->assertOk()
            ->viewData('orders'));

        $this->assertCount(20, $ids);
        $this->assertSame(range(20, 1), $ids);
    }
}
