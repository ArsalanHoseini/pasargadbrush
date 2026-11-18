<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Services\Sms\SmsSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function order(string $status = 'pending'): Order
    {
        $customer = Customer::create(['name' => 'علی', 'phone' => '0912'.random_int(1000000, 9999999)]);

        return Order::create([
            'order_number' => 'PB-'.uniqid(),
            'customer_id' => $customer->id,
            'status' => $status,
            'payment_status' => 'unpaid',
            'subtotal_rial' => 1000,
            'shipping_cost_rial' => 0,
            'total_rial' => 1000,
            'placed_at' => now(),
        ]);
    }

    public function test_bulk_status_change_updates_selected_orders_skips_cancelled_and_is_audited(): void
    {
        $this->app->instance(SmsSender::class, new class implements SmsSender
        {
            public function send(string $mobile, string $message): void
            {
                throw new \LogicException('No SMS should be sent without the notify option.');
            }
        });
        $first = $this->order();
        $second = $this->order();
        $cancelled = $this->order('cancelled');
        $untouched = $this->order();

        $this->actingAs($this->staff())
            ->from(route('admin.orders.index'))
            ->post(route('admin.orders.bulk'), ['ids' => [$first->id, $second->id, $cancelled->id], 'action' => 'status:shipped'])
            ->assertRedirect(route('admin.orders.index'))
            ->assertSessionHas('status', '2 سفارش به‌روزرسانی شد (1 مورد لغوشده بود یا از قبل همین وضعیت را داشت).');

        $this->assertSame('shipped', $first->refresh()->status);
        $this->assertSame('shipped', $second->refresh()->status);
        $this->assertSame('cancelled', $cancelled->refresh()->status);
        $this->assertSame('pending', $untouched->refresh()->status);
        $this->assertSame(['status' => 'shipped', 'bulk' => true], AuditLog::where('auditable_id', $first->id)->sole()->changes);
    }

    public function test_bulk_can_mark_orders_as_paid(): void
    {
        $order = $this->order();

        $this->actingAs($this->staff())->post(route('admin.orders.bulk'), ['ids' => [$order->id], 'action' => 'payment:paid']);

        $this->assertSame('paid', $order->refresh()->payment_status);
    }

    public function test_bulk_order_actions_reject_cancelling(): void
    {
        $order = $this->order();

        $this->actingAs($this->staff())->post(route('admin.orders.bulk'), ['ids' => [$order->id], 'action' => 'status:cancelled'])
            ->assertSessionHasErrors('action');

        $this->assertSame('pending', $order->refresh()->status);
    }

    public function test_bulk_product_actions_toggle_status_and_never_delete_ordered_products(): void
    {
        $unused = Product::create(['sku' => 'A', 'slug' => 'a', 'name' => 'برس الف', 'type' => 'quote', 'is_active' => true]);
        $ordered = Product::create(['sku' => 'B', 'slug' => 'b', 'name' => 'برس ب', 'type' => 'quote', 'is_active' => true]);
        OrderItem::create(['order_id' => $this->order()->id, 'product_id' => $ordered->id, 'sku_snapshot' => 'B', 'name_snapshot' => 'برس ب', 'unit_price_rial' => 0, 'quantity' => 1, 'line_total_rial' => 0]);

        $this->actingAs($this->staff());

        $this->post(route('admin.products.bulk'), ['ids' => [$unused->id, $ordered->id], 'action' => 'deactivate']);
        $this->assertFalse($unused->refresh()->is_active);
        $this->assertFalse($ordered->refresh()->is_active);

        $this->post(route('admin.products.bulk'), ['ids' => [$unused->id, $ordered->id], 'action' => 'delete'])
            ->assertSessionHas('status', '1 محصول حذف شد (1 محصول در سفارش‌ها استفاده شده و حذف نشد).');
        $this->assertModelMissing($unused);
        $this->assertModelExists($ordered);
    }

    public function test_bulk_quote_request_status_change_and_delete(): void
    {
        $first = QuoteRequest::create(['name' => 'الف', 'phone' => '0912', 'message' => 'م', 'status' => 'new']);
        $second = QuoteRequest::create(['name' => 'ب', 'phone' => '0913', 'message' => 'م', 'status' => 'new']);

        $this->actingAs($this->staff());

        $this->post(route('admin.quote-requests.bulk'), ['ids' => [$first->id, $second->id], 'action' => 'status:contacted']);
        $this->assertSame(['contacted', 'contacted'], QuoteRequest::orderBy('id')->pluck('status')->all());

        $this->post(route('admin.quote-requests.bulk'), ['ids' => [$first->id], 'action' => 'delete']);
        $this->assertModelMissing($first);
        $this->assertModelExists($second);
    }

    public function test_bulk_moderation_of_reviews_and_comments(): void
    {
        $product = Product::create(['sku' => 'A', 'slug' => 'a', 'name' => 'برس', 'type' => 'quote']);
        $review = ProductReview::create(['product_id' => $product->id, 'author_name' => 'رضا', 'body' => 'خوب', 'status' => 'pending']);
        $post = BlogPost::create(['title' => 'مقاله', 'slug' => 'm', 'body' => 'x', 'status' => 'published', 'published_at' => now()]);
        $comment = Comment::create(['blog_post_id' => $post->id, 'author_name' => 'سارا', 'author_email' => 's@example.com', 'body' => 'عالی', 'status' => 'pending']);

        $this->actingAs($this->staff());

        $this->post(route('admin.product-reviews.bulk'), ['ids' => [$review->id], 'action' => 'approve']);
        $this->post(route('admin.comments.bulk'), ['ids' => [$comment->id], 'action' => 'spam']);

        $this->assertSame('approved', $review->refresh()->status);
        $this->assertSame('spam', $comment->refresh()->status);
    }

    public function test_lists_render_the_bulk_selection_controls(): void
    {
        $this->order();

        $this->actingAs($this->staff())->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('x-data="bulkSelection"', false)
            ->assertSee('id="bulk-form"', false)
            ->assertSee('data-bulk-id', false);
    }

    public function test_bulk_actions_require_a_selection(): void
    {
        $this->actingAs($this->staff())->post(route('admin.products.bulk'), ['action' => 'activate'])
            ->assertSessionHasErrors(['ids' => 'هیچ محصولی انتخاب نشده است.']);
    }
}
