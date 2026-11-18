<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaffRolesTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::factory()->create(['role' => 'editor']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function adminOnlyPages(): array
    {
        return [
            'orders' => ['/admin/orders'],
            'order export' => ['/admin/orders/export'],
            'customers' => ['/admin/customers'],
            'quote requests' => ['/admin/quote-requests'],
            'conversations' => ['/admin/conversations'],
            'reports' => ['/admin/reports'],
            'settings' => ['/admin/settings'],
            'shipping' => ['/admin/shipping'],
            'redirects' => ['/admin/redirects'],
            'import' => ['/admin/import'],
            'users' => ['/admin/users'],
            'activity' => ['/admin/activity'],
        ];
    }

    #[DataProvider('adminOnlyPages')]
    public function test_editors_cannot_open_admin_only_pages(string $url): void
    {
        $this->actingAs($this->editor())->get($url)->assertForbidden();
    }

    #[DataProvider('adminOnlyPages')]
    public function test_admins_can_open_admin_only_pages(string $url): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get($url)->assertSuccessful();
    }

    public function test_editors_cannot_change_orders_or_settings(): void
    {
        $customer = Customer::create(['name' => 'علی', 'phone' => '09120000000']);
        $order = $customer->orders()->create(['order_number' => 'PB-1', 'status' => 'pending', 'payment_status' => 'unpaid', 'subtotal_rial' => 0, 'shipping_cost_rial' => 0, 'total_rial' => 0, 'placed_at' => now()]);

        $this->actingAs($this->editor());

        $this->post(route('admin.orders.bulk'), ['ids' => [$order->id], 'action' => 'status:shipped'])->assertForbidden();
        $this->put(route('admin.settings.update'), [])->assertForbidden();
        $this->post(route('admin.users.store'), ['name' => 'x', 'email' => 'x@example.com', 'role' => 'admin', 'password' => 'secret123', 'password_confirmation' => 'secret123'])->assertForbidden();

        $this->assertSame('pending', $order->refresh()->status);
        $this->assertSame(1, User::count());
    }

    public function test_editors_keep_catalog_and_content(): void
    {
        $this->actingAs($this->editor());

        foreach (['/admin/products', '/admin/categories', '/admin/applications', '/admin/product-reviews', '/admin/blog-posts', '/admin/comments', '/admin/pages', '/admin/media', '/admin/profile'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_editor_sidebar_and_dashboard_hide_sales(): void
    {
        Customer::create(['name' => 'علی', 'phone' => '09120000000'])->orders()->create(['order_number' => 'PB-1', 'status' => 'pending', 'payment_status' => 'unpaid', 'subtotal_rial' => 0, 'shipping_cost_rial' => 0, 'total_rial' => 0, 'placed_at' => now()]);

        $this->actingAs($this->editor())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('محصولات')
            ->assertSee('مقالات')
            ->assertDontSee('سفارش‌ها')
            ->assertDontSee('تنظیمات سایت')
            ->assertDontSee('فروش ۳۰ روز اخیر')
            ->assertDontSee('سفارش جدید در انتظار بررسی')
            ->assertDontSee(route('admin.orders.create'), false);
    }

    public function test_admin_sidebar_lists_the_new_sections(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.dashboard'))
            ->assertSee('گزارش فروش')
            ->assertSee('کاربران پنل')
            ->assertSee('گزارش فعالیت');
    }

    public function test_editor_search_only_returns_products(): void
    {
        Product::create(['sku' => 'S-1', 'slug' => 's1', 'name' => 'برس سیمی', 'type' => 'quote']);
        Customer::create(['name' => 'شرکت سیمین', 'phone' => '09121111111']);
        $order = Customer::first()->orders()->create(['order_number' => 'PB-777', 'status' => 'pending', 'payment_status' => 'unpaid', 'subtotal_rial' => 0, 'shipping_cost_rial' => 0, 'total_rial' => 0, 'placed_at' => now()]);

        $this->actingAs($this->editor());

        $this->get(route('admin.search', ['q' => 'سیم']))->assertOk()->assertSee('برس سیمی')->assertDontSee('شرکت سیمین');
        $this->get(route('admin.search', ['q' => $order->order_number]))->assertOk()->assertDontSee(route('admin.orders.edit', $order), false);
    }
}
