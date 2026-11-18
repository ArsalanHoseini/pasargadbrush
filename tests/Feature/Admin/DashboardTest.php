<?php

namespace Tests\Feature\Admin;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Admin\DashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function order(int $totalRial, Carbon $placedAt, string $status = 'confirmed', array $attributes = []): Order
    {
        $customer = Customer::create(['name' => 'علی', 'phone' => '0912'.random_int(1000000, 9999999)]);

        return Order::create([
            'order_number' => 'PB-TEST-'.uniqid(),
            'customer_id' => $customer->id,
            'status' => $status,
            'payment_status' => 'unpaid',
            'subtotal_rial' => $totalRial,
            'shipping_cost_rial' => 0,
            'total_rial' => $totalRial,
            'placed_at' => $placedAt,
            ...$attributes,
        ]);
    }

    public function test_sales_periods_sum_non_cancelled_orders_and_compare_with_the_previous_period(): void
    {
        $this->travelTo(Carbon::parse('2026-11-14 12:00'));

        $this->order(50_000, now());
        $this->order(30_000, now()->subHour());
        $this->order(999_000, now(), 'cancelled');
        $this->order(40_000, now()->subDay());

        $today = app(DashboardMetrics::class)->salesPeriods()[0];

        $this->assertSame(80_000, $today['revenue_rial']);
        $this->assertSame(2, $today['order_count']);
        $this->assertSame(40_000, $today['previous_revenue_rial']);
        $this->assertSame(100, $today['change_percent']);
    }

    public function test_change_percent_is_null_without_previous_sales(): void
    {
        $this->order(50_000, now());

        $this->assertNull(app(DashboardMetrics::class)->salesPeriods()[0]['change_percent']);
    }

    public function test_daily_sales_covers_thirty_days_ending_today(): void
    {
        $this->travelTo(Carbon::parse('2026-11-14 12:00'));
        $this->order(20_000, now()->subDays(3));
        $this->order(10_000, now()->subDays(3));
        $this->order(70_000, now()->subDays(40));

        $days = app(DashboardMetrics::class)->dailySales();

        $this->assertCount(DashboardMetrics::CHART_DAYS, $days);
        $this->assertSame('2026-11-14', $days[29]['date']->toDateString());
        $this->assertSame(30_000, $days[26]['revenue_rial']);
        $this->assertSame(2, $days[26]['order_count']);
        $this->assertSame(30_000, array_sum(array_column($days, 'revenue_rial')));
    }

    public function test_action_items_list_only_what_needs_attention(): void
    {
        $this->order(10_000, now(), 'pending', ['requires_price_confirmation' => true]);
        $this->order(10_000, now(), 'completed', ['requires_price_confirmation' => true]);

        $labels = array_column(app(DashboardMetrics::class)->actionItems(), 'count', 'label');

        $this->assertSame(1, $labels['سفارش منتظر تأیید قیمت نهایی']);
        $this->assertSame(1, $labels['سفارش جدید در انتظار بررسی']);
        $this->assertArrayNotHasKey('پیام بی‌پاسخ مشتری', $labels);
    }

    public function test_low_stock_counts_only_tracked_variants_at_or_below_the_threshold(): void
    {
        $product = Product::create(['sku' => 'SKU-1', 'slug' => 'p1', 'name' => 'برس', 'type' => 'priced']);
        ProductVariant::create(['product_id' => $product->id, 'sku' => 'V1', 'label' => 'A', 'price_rial' => 1000, 'stock_quantity' => 2]);
        ProductVariant::create(['product_id' => $product->id, 'sku' => 'V2', 'label' => 'B', 'price_rial' => 1000, 'stock_quantity' => 50]);
        ProductVariant::create(['product_id' => $product->id, 'sku' => 'V3', 'label' => 'C', 'price_rial' => 1000, 'stock_quantity' => null]);

        $this->assertSame(1, app(DashboardMetrics::class)->lowStockVariants()->count());
    }

    public function test_top_products_rank_by_revenue_and_skip_cancelled_orders(): void
    {
        $small = $this->order(10_000, now());
        $big = $this->order(90_000, now());
        $cancelled = $this->order(500_000, now(), 'cancelled');
        $product = Product::create(['sku' => 'SKU-1', 'slug' => 'p1', 'name' => 'برس', 'type' => 'priced']);
        $otherProduct = Product::create(['sku' => 'SKU-2', 'slug' => 'p2', 'name' => 'برس ۲', 'type' => 'priced']);
        $cancelledProduct = Product::create(['sku' => 'SKU-3', 'slug' => 'p3', 'name' => 'برس ۳', 'type' => 'priced']);

        OrderItem::create(['order_id' => $small->id, 'product_id' => $product->id, 'sku_snapshot' => 'S', 'name_snapshot' => 'برس کوچک', 'unit_price_rial' => 10_000, 'quantity' => 1, 'line_total_rial' => 10_000]);
        OrderItem::create(['order_id' => $big->id, 'product_id' => $otherProduct->id, 'sku_snapshot' => 'S', 'name_snapshot' => 'برس بزرگ', 'unit_price_rial' => 45_000, 'quantity' => 2, 'line_total_rial' => 90_000]);
        OrderItem::create(['order_id' => $cancelled->id, 'product_id' => $cancelledProduct->id, 'sku_snapshot' => 'S', 'name_snapshot' => 'لغوشده', 'unit_price_rial' => 500_000, 'quantity' => 1, 'line_total_rial' => 500_000]);

        $top = app(DashboardMetrics::class)->topProducts();

        $this->assertSame(['برس بزرگ', 'برس کوچک'], $top->pluck('name')->all());
    }

    public function test_dashboard_page_renders_kpis_chart_and_action_items(): void
    {
        $this->order(120_000, now(), 'pending');
        Conversation::create([
            'customer_id' => Customer::create(['name' => 'رضا', 'phone' => '09130000000'])->id,
            'subject' => 'سؤال',
            'status' => Conversation::STATUS_OPEN,
            'last_message_at' => now(),
        ]);

        $this->actingAs($this->staff())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('فروش ۳۰ روز اخیر')
            ->assertSee('12,000 تومان')
            ->assertSee('سفارش جدید در انتظار بررسی')
            ->assertSee('پیام بی‌پاسخ مشتری');
    }

    public function test_dashboard_shows_empty_states_without_data(): void
    {
        $this->actingAs($this->staff())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('در ۳۰ روز اخیر سفارشی ثبت نشده است.')
            ->assertSee('کار معوقی وجود ندارد.');
    }
}
