<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function order(string $orderNumber, string $customerName): Order
    {
        $customer = Customer::create(['name' => $customerName, 'phone' => '0912'.random_int(1000000, 9999999)]);

        return Order::create([
            'order_number' => $orderNumber,
            'customer_id' => $customer->id,
            'customer_name' => $customerName,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'subtotal_rial' => 1000,
            'shipping_cost_rial' => 0,
            'total_rial' => 1000,
            'placed_at' => now(),
        ]);
    }

    public function test_search_finds_products_customers_orders_and_quote_requests(): void
    {
        Product::create(['sku' => 'BR-77', 'slug' => 'p1', 'name' => 'برس سیمی کاسه‌ای', 'type' => 'quote']);
        Customer::create(['name' => 'شرکت سیمین', 'phone' => '09121111111']);
        $this->order('PB-1001', 'سیمین تهرانی');
        QuoteRequest::create(['name' => 'سیما', 'phone' => '0912', 'message' => 'م']);

        $this->actingAs($this->staff())->get(route('admin.search', ['q' => 'سیم']))
            ->assertOk()
            ->assertSee('برس سیمی کاسه‌ای')
            ->assertSee('شرکت سیمین')
            ->assertSee('PB-1001')
            ->assertSee('سیما');
    }

    public function test_an_exact_order_number_opens_that_order(): void
    {
        $order = $this->order('PB-2002', 'علی');

        $this->actingAs($this->staff())->get(route('admin.search', ['q' => 'PB-2002']))
            ->assertRedirect(route('admin.orders.edit', $order));
    }

    public function test_persian_digits_are_matched_as_latin_digits(): void
    {
        Customer::create(['name' => 'رضا', 'phone' => '09125556677']);

        $this->actingAs($this->staff())->get(route('admin.search', ['q' => '۵۵۵۶۶']))
            ->assertOk()
            ->assertSee('رضا');
    }

    public function test_no_results_shows_a_message(): void
    {
        $this->actingAs($this->staff())->get(route('admin.search', ['q' => 'ناموجود']))
            ->assertOk()
            ->assertSee('نتیجه‌ای برای «ناموجود» پیدا نشد.');
    }

    public function test_guests_cannot_search(): void
    {
        $this->get(route('admin.search', ['q' => 'x']))->assertRedirect(route('admin.login'));
    }
}
