<?php

namespace Tests\Feature\Admin;

use App\Models\BlogPost;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListFilterTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function order(string $orderNumber, array $attributes = []): Order
    {
        $customer = Customer::create(['name' => 'علی', 'phone' => '0912'.random_int(1000000, 9999999)]);

        return Order::create([
            'order_number' => $orderNumber,
            'customer_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'subtotal_rial' => 1000,
            'shipping_cost_rial' => 0,
            'total_rial' => 1000,
            'placed_at' => now(),
            ...$attributes,
        ]);
    }

    public function test_orders_can_be_searched_and_filtered_by_payment_and_price_confirmation(): void
    {
        $this->order('PB-PAID', ['payment_status' => 'paid', 'customer_phone' => '09121234567']);
        $this->order('PB-UNPAID');
        $this->order('PB-PRICE', ['requires_price_confirmation' => true]);

        $this->actingAs($this->staff());

        $this->get(route('admin.orders.index', ['q' => '۱۲۳۴۵']))
            ->assertSee('PB-PAID')->assertDontSee('PB-UNPAID');

        $this->get(route('admin.orders.index', ['payment_status' => 'paid']))
            ->assertSee('PB-PAID')->assertDontSee('PB-UNPAID');

        $this->get(route('admin.orders.index', ['price_confirmation' => 1]))
            ->assertSee('PB-PRICE')->assertDontSee('PB-PAID');
    }

    public function test_products_can_be_filtered_by_search_category_type_status_and_low_stock(): void
    {
        $category = ProductCategory::create(['name' => 'برس صنعتی', 'slug' => 'brush']);
        $priced = Product::create(['sku' => 'P-1', 'slug' => 'p1', 'name' => 'برس قیمت‌دار', 'type' => 'priced', 'is_active' => true, 'primary_category_id' => $category->id]);
        Product::create(['sku' => 'Q-1', 'slug' => 'q1', 'name' => 'برس استعلامی', 'type' => 'quote', 'is_active' => false]);
        ProductVariant::create(['product_id' => $priced->id, 'sku' => 'P-1-A', 'label' => 'A', 'price_rial' => 1000, 'stock_quantity' => 1]);

        $this->actingAs($this->staff());

        $this->get(route('admin.products.index', ['q' => 'Q-1']))->assertSee('برس استعلامی')->assertDontSee('برس قیمت‌دار');
        $this->get(route('admin.products.index', ['category' => $category->id]))->assertSee('برس قیمت‌دار')->assertDontSee('برس استعلامی');
        $this->get(route('admin.products.index', ['type' => 'quote']))->assertSee('برس استعلامی')->assertDontSee('برس قیمت‌دار');
        $this->get(route('admin.products.index', ['status' => 'inactive']))->assertSee('برس استعلامی')->assertDontSee('برس قیمت‌دار');
        $this->get(route('admin.products.index', ['stock' => 'low']))->assertSee('برس قیمت‌دار')->assertDontSee('برس استعلامی');
    }

    public function test_filtered_product_pages_do_not_overlap_when_created_at_ties(): void
    {
        $createdAt = now()->subDay();
        foreach (range(1, 25) as $number) {
            Product::create(['sku' => "Q-{$number}", 'slug' => "q{$number}", 'name' => "برس-شماره-{$number}-پایان", 'type' => 'quote', 'is_active' => true])
                ->forceFill(['created_at' => $createdAt])->saveQuietly();
        }

        $this->actingAs($this->staff());

        $firstPage = $this->get(route('admin.products.index', ['type' => 'quote']))->viewData('products');
        $secondPage = $this->get(route('admin.products.index', ['type' => 'quote', 'page' => 2]))->viewData('products');

        $this->assertSame(range(25, 6), $firstPage->pluck('sku')->map(fn (string $sku) => (int) substr($sku, 2))->all());
        $this->assertSame(range(5, 1), $secondPage->pluck('sku')->map(fn (string $sku) => (int) substr($sku, 2))->all());
        $this->assertStringContainsString('type=quote', $firstPage->nextPageUrl());
    }

    public function test_order_status_tabs_keep_the_other_filters(): void
    {
        $this->order('PB-1');

        $this->actingAs($this->staff())->get(route('admin.orders.index', ['q' => 'PB', 'payment_status' => 'paid', 'page' => 2]))
            ->assertSee(e(route('admin.orders.index', ['status' => 'pending', 'q' => 'PB', 'payment_status' => 'paid'])), false)
            ->assertSee(e(route('admin.orders.index', ['q' => 'PB', 'payment_status' => 'paid'])), false);
    }

    public function test_product_filters_with_no_match_show_a_filtered_empty_state(): void
    {
        $this->actingAs($this->staff())->get(route('admin.products.index', ['q' => 'هیچ']))
            ->assertOk()
            ->assertSee('محصولی با این فیلترها پیدا نشد.');
    }

    public function test_customers_can_be_searched_and_filtered_by_having_orders(): void
    {
        Customer::create(['name' => 'مشتری بی‌سفارش', 'phone' => '09120000001']);
        $this->order('PB-1');

        $this->actingAs($this->staff());

        $this->get(route('admin.customers.index', ['q' => 'بی‌سفارش']))->assertSee('مشتری بی‌سفارش');
        $this->get(route('admin.customers.index', ['has_orders' => 'yes']))->assertDontSee('مشتری بی‌سفارش');
        $this->get(route('admin.customers.index', ['has_orders' => 'no']))->assertSee('مشتری بی‌سفارش');
    }

    public function test_quote_requests_search_keeps_the_status_tab(): void
    {
        QuoteRequest::create(['name' => 'کامران', 'phone' => '0912', 'message' => 'م', 'status' => 'new']);
        QuoteRequest::create(['name' => 'کامبیز', 'phone' => '0913', 'message' => 'م', 'status' => 'closed']);

        $this->actingAs($this->staff())->get(route('admin.quote-requests.index', ['status' => 'new', 'q' => 'کام']))
            ->assertSee('کامران')
            ->assertDontSee('کامبیز');
    }

    public function test_blog_posts_can_be_filtered_by_status(): void
    {
        BlogPost::create(['title' => 'مقاله منتشرشده', 'slug' => 'a', 'body' => 'x', 'status' => 'published', 'published_at' => now()]);
        BlogPost::create(['title' => 'مقاله پیش‌نویس', 'slug' => 'b', 'body' => 'x', 'status' => 'draft']);

        $this->actingAs($this->staff())->get(route('admin.blog-posts.index', ['status' => 'draft']))
            ->assertSee('مقاله پیش‌نویس')
            ->assertDontSee('مقاله منتشرشده');
    }
}
