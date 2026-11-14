<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_shows_purchase_stats_without_cancelled_orders_and_quote_requests(): void
    {
        $customer = Customer::create(['name' => 'رضا', 'company_name' => 'صنایع رضا', 'phone' => '09125556677']);

        foreach ([[1_000_000, 'completed', 'paid'], [3_000_000, 'pending', 'unpaid'], [9_000_000, 'cancelled', 'unpaid']] as $index => [$total, $status, $payment]) {
            $customer->orders()->create([
                'order_number' => 'PB-C-'.$index, 'status' => $status, 'payment_status' => $payment,
                'subtotal_rial' => $total, 'shipping_cost_rial' => 0, 'total_rial' => $total, 'placed_at' => now()->subDays(3 - $index),
            ]);
        }
        QuoteRequest::create(['customer_id' => $customer->id, 'name' => 'رضا', 'phone' => '09125556677', 'message' => 'م', 'status' => 'new']);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('صنایع رضا')
            ->assertSeeInOrder(['سفارش‌ها', '2', 'مجموع خرید', '400,000 تومان', 'میانگین هر سفارش', '200,000 تومان'])
            ->assertSee('1 سفارش پرداخت‌نشده')
            ->assertSee('استعلام عمومی')
            ->assertSee('PB-C-2');
    }

    public function test_customer_list_filters_by_join_date(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 14));
        Customer::create(['name' => 'مشتری قدیمی', 'phone' => '09120000001'])->forceFill(['created_at' => now()->subYear()])->save();
        Customer::create(['name' => 'مشتری جدید', 'phone' => '09120000002']);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.customers.index', ['from' => '1405/08/01']))
            ->assertSee('مشتری جدید')
            ->assertDontSee('مشتری قدیمی');
    }
}
